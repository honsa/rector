<?php

declare (strict_types=1);
namespace Rector\Symfony\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Type\ObjectType;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\MethodName;
use Rector\Symfony\NodeFactory\FormType\BuildFormOptionAssignsFactory;
use Rector\Symfony\NodeRemover\ConstructorDependencyRemover;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://speakerdeck.com/webmozart/symfony-forms-101?slide=24
 *
 * @see \Rector\Symfony\Tests\Rector\Class_\FormTypeWithDependencyToOptionsRector\FormTypeWithDependencyToOptionsRectorTest
 */
final class FormTypeWithDependencyToOptionsRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @readonly
     * @var \Rector\Symfony\NodeFactory\FormType\BuildFormOptionAssignsFactory
     */
    private $buildFormOptionAssignsFactory;
    /**
     * @readonly
     * @var \Rector\Symfony\NodeRemover\ConstructorDependencyRemover
     */
    private $constructorDependencyRemover;
    public function __construct(\Rector\Symfony\NodeFactory\FormType\BuildFormOptionAssignsFactory $buildFormOptionAssignsFactory, \Rector\Symfony\NodeRemover\ConstructorDependencyRemover $constructorDependencyRemover)
    {
        $this->buildFormOptionAssignsFactory = $buildFormOptionAssignsFactory;
        $this->constructorDependencyRemover = $constructorDependencyRemover;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Move constructor dependency from form type class to an $options parameter', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormTypeWithDependency extends AbstractType
{
    private Agent $agent;

    public function __construct(Agent $agent)
    {
        $this->agent = $agent;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->agent) {
            $builder->add('agent', TextType::class);
        }
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormTypeWithDependency extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $agent = $options['agent'];

        if ($agent) {
            $builder->add('agent', TextType::class);
        }
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(\PhpParser\Node $node) : ?\PhpParser\Node
    {
        $formObjectType = new \PHPStan\Type\ObjectType('Symfony\\Component\\Form\\AbstractType');
        if (!$this->isObjectType($node, $formObjectType)) {
            return null;
        }
        // skip abstract
        if ($node->isAbstract()) {
            return null;
        }
        $constructorClassMethod = $node->getMethod(\Rector\Core\ValueObject\MethodName::CONSTRUCT);
        if (!$constructorClassMethod instanceof \PhpParser\Node\Stmt\ClassMethod) {
            return null;
        }
        $params = $constructorClassMethod->getParams();
        if ($params === []) {
            return null;
        }
        $buildFormClassMethod = $node->getMethod('buildForm');
        if (!$buildFormClassMethod instanceof \PhpParser\Node\Stmt\ClassMethod) {
            // form has to have some items
            throw new \Rector\Core\Exception\ShouldNotHappenException();
        }
        $paramNames = $this->nodeNameResolver->getNames($params);
        // 1. add assigns at start of ClassMethod
        $assignExpressions = $this->buildFormOptionAssignsFactory->createDimFetchAssignsFromParamNames($paramNames);
        $buildFormClassMethod->stmts = \array_merge($assignExpressions, (array) $buildFormClassMethod->stmts);
        // 2. remove properties
        foreach ($node->getProperties() as $property) {
            if (!$this->isNames($property, $paramNames)) {
                continue;
            }
            $this->removeNode($property);
        }
        // 3. cleanup ctor
        $this->constructorDependencyRemover->removeParamsByName($constructorClassMethod, $paramNames);
        $this->replacePropertyFetchesByVariables($buildFormClassMethod, $paramNames);
        return $node;
    }
    /**
     * 4. replace property fetches in buildForm() by just assigned variable
     *
     * @param string[] $paramNames
     */
    private function replacePropertyFetchesByVariables(\PhpParser\Node\Stmt\ClassMethod $classMethod, array $paramNames) : void
    {
        $this->traverseNodesWithCallable((array) $classMethod->stmts, function (\PhpParser\Node $node) use($paramNames) : ?Variable {
            if (!$node instanceof \PhpParser\Node\Expr\PropertyFetch) {
                return null;
            }
            if (!$this->nodeNameResolver->isName($node->var, 'this')) {
                return null;
            }
            if (!$this->nodeNameResolver->isNames($node->name, $paramNames)) {
                return null;
            }
            // replace by variable
            $variableName = $this->getName($node->name);
            if (!\is_string($variableName)) {
                return null;
            }
            return new \PhpParser\Node\Expr\Variable($variableName);
        });
    }
}