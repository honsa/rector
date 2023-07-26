<?php

declare (strict_types=1);
namespace Rector\Core\NodeManipulator;

use RectorPrefix202307\Doctrine\ORM\Mapping\Table;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Core\NodeAnalyzer\PropertyFetchAnalyzer;
use Rector\Core\PhpParser\ClassLikeAstResolver;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder;
use Rector\Core\Reflection\ReflectionResolver;
use Rector\Core\ValueObject\MethodName;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\NodeTypeResolver\PHPStan\ParametersAcceptorSelectorVariantsWrapper;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\Php80\NodeAnalyzer\PromotedPropertyResolver;
use Rector\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector;
/**
 * For inspiration to improve this service,
 * @see examples of variable modifications in https://wiki.php.net/rfc/readonly_properties_v2#proposal
 */
final class PropertyManipulator
{
    /**
     * @readonly
     * @var \Rector\Core\NodeManipulator\AssignManipulator
     */
    private $assignManipulator;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory
     */
    private $phpDocInfoFactory;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder
     */
    private $propertyFetchFinder;
    /**
     * @readonly
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @readonly
     * @var \Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer
     */
    private $phpAttributeAnalyzer;
    /**
     * @readonly
     * @var \Rector\NodeTypeResolver\NodeTypeResolver
     */
    private $nodeTypeResolver;
    /**
     * @readonly
     * @var \Rector\Php80\NodeAnalyzer\PromotedPropertyResolver
     */
    private $promotedPropertyResolver;
    /**
     * @readonly
     * @var \Rector\TypeDeclaration\AlreadyAssignDetector\ConstructorAssignDetector
     */
    private $constructorAssignDetector;
    /**
     * @readonly
     * @var \Rector\Core\PhpParser\ClassLikeAstResolver
     */
    private $classLikeAstResolver;
    /**
     * @readonly
     * @var \Rector\Core\NodeAnalyzer\PropertyFetchAnalyzer
     */
    private $propertyFetchAnalyzer;
    /**
     * @readonly
     * @var \Rector\Core\Reflection\ReflectionResolver
     */
    private $reflectionResolver;
    /**
     * @var string[]|class-string<Table>[]
     */
    private const DOCTRINE_PROPERTY_ANNOTATIONS = ['Doctrine\\ORM\\Mapping\\Entity', 'Doctrine\\ORM\\Mapping\\Table', 'Doctrine\\ORM\\Mapping\\MappedSuperclass'];
    public function __construct(\Rector\Core\NodeManipulator\AssignManipulator $assignManipulator, BetterNodeFinder $betterNodeFinder, PhpDocInfoFactory $phpDocInfoFactory, PropertyFetchFinder $propertyFetchFinder, NodeNameResolver $nodeNameResolver, PhpAttributeAnalyzer $phpAttributeAnalyzer, NodeTypeResolver $nodeTypeResolver, PromotedPropertyResolver $promotedPropertyResolver, ConstructorAssignDetector $constructorAssignDetector, ClassLikeAstResolver $classLikeAstResolver, PropertyFetchAnalyzer $propertyFetchAnalyzer, ReflectionResolver $reflectionResolver)
    {
        $this->assignManipulator = $assignManipulator;
        $this->betterNodeFinder = $betterNodeFinder;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
        $this->propertyFetchFinder = $propertyFetchFinder;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->phpAttributeAnalyzer = $phpAttributeAnalyzer;
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->promotedPropertyResolver = $promotedPropertyResolver;
        $this->constructorAssignDetector = $constructorAssignDetector;
        $this->classLikeAstResolver = $classLikeAstResolver;
        $this->propertyFetchAnalyzer = $propertyFetchAnalyzer;
        $this->reflectionResolver = $reflectionResolver;
    }
    /**
     * @param \PhpParser\Node\Stmt\Property|\PhpParser\Node\Param $propertyOrParam
     */
    public function isPropertyChangeableExceptConstructor(Class_ $class, $propertyOrParam, Scope $scope) : bool
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($class);
        if ($this->hasAllowedNotReadonlyAnnotationOrAttribute($phpDocInfo, $class)) {
            return \true;
        }
        $propertyFetches = $this->propertyFetchFinder->findPrivatePropertyFetches($class, $propertyOrParam);
        $classMethod = $class->getMethod(MethodName::CONSTRUCT);
        foreach ($propertyFetches as $propertyFetch) {
            if ($this->isChangeableContext($propertyFetch, $scope, $classMethod)) {
                return \true;
            }
            // skip for constructor? it is allowed to set value in constructor method
            $propertyName = (string) $this->nodeNameResolver->getName($propertyFetch);
            if ($this->isPropertyAssignedOnlyInConstructor($class, $propertyName, $propertyFetch, $classMethod)) {
                continue;
            }
            if ($this->assignManipulator->isLeftPartOfAssign($propertyFetch)) {
                return \true;
            }
            if ($propertyFetch->getAttribute(AttributeKey::IS_UNSET_VAR) === \true) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @api Used in rector-symfony
     */
    public function resolveExistingClassPropertyNameByType(Class_ $class, ObjectType $objectType) : ?string
    {
        foreach ($class->getProperties() as $property) {
            $propertyType = $this->nodeTypeResolver->getType($property);
            if (!$propertyType->equals($objectType)) {
                continue;
            }
            return $this->nodeNameResolver->getName($property);
        }
        $promotedPropertyParams = $this->promotedPropertyResolver->resolveFromClass($class);
        foreach ($promotedPropertyParams as $promotedPropertyParam) {
            $paramType = $this->nodeTypeResolver->getType($promotedPropertyParam);
            if (!$paramType->equals($objectType)) {
                continue;
            }
            return $this->nodeNameResolver->getName($promotedPropertyParam);
        }
        return null;
    }
    public function isUsedByTrait(ClassReflection $classReflection, string $propertyName) : bool
    {
        foreach ($classReflection->getTraits() as $traitUse) {
            $trait = $this->classLikeAstResolver->resolveClassFromClassReflection($traitUse);
            if (!$trait instanceof Trait_) {
                continue;
            }
            if ($this->propertyFetchAnalyzer->containsLocalPropertyFetchName($trait, $propertyName)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param \PhpParser\Node\Expr\StaticPropertyFetch|\PhpParser\Node\Expr\PropertyFetch $propertyFetch
     */
    private function isPropertyAssignedOnlyInConstructor(Class_ $class, string $propertyName, $propertyFetch, ?ClassMethod $classMethod) : bool
    {
        if (!$classMethod instanceof ClassMethod) {
            return \false;
        }
        $node = $this->betterNodeFinder->findFirst((array) $classMethod->stmts, static function (Node $subNode) use($propertyFetch) : bool {
            return ($subNode instanceof PropertyFetch || $subNode instanceof StaticPropertyFetch) && $subNode === $propertyFetch;
        });
        // there is property unset in Test class, so only check on __construct
        if (!$node instanceof Node) {
            return \false;
        }
        return $this->constructorAssignDetector->isPropertyAssigned($class, $propertyName);
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     * @return \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|null
     */
    private function resolveCaller($propertyFetch, ?ClassMethod $classMethod)
    {
        if (!$classMethod instanceof ClassMethod) {
            return null;
        }
        return $this->betterNodeFinder->findFirstInFunctionLikeScoped($classMethod, static function (Node $subNode) use($propertyFetch) : bool {
            if (!$subNode instanceof MethodCall && !$subNode instanceof StaticCall) {
                return \false;
            }
            if ($subNode->isFirstClassCallable()) {
                return \false;
            }
            foreach ($subNode->getArgs() as $arg) {
                if ($arg->value === $propertyFetch) {
                    return \true;
                }
            }
            return \false;
        });
    }
    /**
     * @param \PhpParser\Node\Expr\PropertyFetch|\PhpParser\Node\Expr\StaticPropertyFetch $propertyFetch
     */
    private function isChangeableContext($propertyFetch, Scope $scope, ?ClassMethod $classMethod) : bool
    {
        if ($propertyFetch->getAttribute(AttributeKey::IS_UNSET_VAR, \false)) {
            return \true;
        }
        if ($propertyFetch->getAttribute(AttributeKey::IS_ARG_VALUE)) {
            $caller = $this->resolveCaller($propertyFetch, $classMethod);
            return $this->isFoundByRefParam($caller, $scope);
        }
        return $propertyFetch->getAttribute(AttributeKey::INSIDE_ARRAY_DIM_FETCH, \false);
    }
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|null $node
     */
    private function isFoundByRefParam($node, Scope $scope) : bool
    {
        if ($node === null) {
            return \false;
        }
        $functionLikeReflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall($node);
        if ($functionLikeReflection === null) {
            return \false;
        }
        $parametersAcceptor = ParametersAcceptorSelectorVariantsWrapper::select($functionLikeReflection, $node, $scope);
        foreach ($parametersAcceptor->getParameters() as $parameterReflection) {
            if ($parameterReflection->passedByReference()->yes()) {
                return \true;
            }
        }
        return \false;
    }
    private function hasAllowedNotReadonlyAnnotationOrAttribute(PhpDocInfo $phpDocInfo, Class_ $class) : bool
    {
        if ($phpDocInfo->hasByAnnotationClasses(self::DOCTRINE_PROPERTY_ANNOTATIONS)) {
            return \true;
        }
        return $this->phpAttributeAnalyzer->hasPhpAttributes($class, self::DOCTRINE_PROPERTY_ANNOTATIONS);
    }
}
