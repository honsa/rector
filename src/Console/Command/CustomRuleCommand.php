<?php

declare (strict_types=1);
namespace Rector\Console\Command;

use RectorPrefix202402\Nette\Utils\FileSystem;
use RectorPrefix202402\Nette\Utils\Strings;
use Rector\Exception\ShouldNotHappenException;
use Rector\FileSystem\JsonFileSystem;
use RectorPrefix202402\Symfony\Component\Console\Command\Command;
use RectorPrefix202402\Symfony\Component\Console\Input\InputInterface;
use RectorPrefix202402\Symfony\Component\Console\Output\OutputInterface;
use RectorPrefix202402\Symfony\Component\Console\Style\SymfonyStyle;
use RectorPrefix202402\Symfony\Component\Finder\Finder;
use RectorPrefix202402\Symfony\Component\Finder\SplFileInfo;
final class CustomRuleCommand extends Command
{
    /**
     * @readonly
     * @var \Symfony\Component\Console\Style\SymfonyStyle
     */
    private $symfonyStyle;
    /**
     * @see https://regex101.com/r/2eP4rw/1
     * @var string
     */
    private const START_WITH_10_REGEX = '#(\\^10\\.|>=10\\.|10\\.)#';
    public function __construct(SymfonyStyle $symfonyStyle)
    {
        $this->symfonyStyle = $symfonyStyle;
        parent::__construct();
    }
    protected function configure() : void
    {
        $this->setName('custom-rule');
        $this->setDescription('Create base of local custom rule with tests');
    }
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        // ask for rule name
        $rectorName = $this->symfonyStyle->ask('What is the name of the rule class (e.g. "LegacyCallToDbalMethodCall")?', null, static function (string $answer) : string {
            if ($answer === '') {
                throw new ShouldNotHappenException('Rector name cannot be empty');
            }
            return $answer;
        });
        // suffix with Rector by convention
        if (\substr_compare((string) $rectorName, 'Rector', -\strlen('Rector')) !== 0) {
            $rectorName .= 'Rector';
        }
        $rectorName = \ucfirst((string) $rectorName);
        // find all files in templates directory
        $finder = Finder::create()->files()->in(__DIR__ . '/../../../templates/custom-rule')->notName('__Name__Test.php');
        // 0. resolve if local phpunit is at least PHPUnit 10 (which supports #[DataProvider])
        // to provide annotation if not
        $arePHPUnitAttributesSupported = $this->detectPHPUnitAttributeSupport();
        if ($arePHPUnitAttributesSupported) {
            $finder->append([new SplFileInfo(__DIR__ . '/../../../templates/custom-rule/utils/rector/tests/Rector/__Name__/__Name__Test.php', 'utils/rector/tests/Rector/__Name__', 'utils/rector/tests/Rector/__Name__/__Name__Test.php')]);
        } else {
            // use @annotations for PHPUnit 9 and bellow
            $finder->append([new SplFileInfo(__DIR__ . '/../../../templates/custom-rules-annotations/utils/rector/tests/Rector/__Name__/__Name__Test.php', 'utils/rector/tests/Rector/__Name__', 'utils/rector/tests/Rector/__Name__/__Name__Test.php')]);
        }
        $generatedFilePaths = [];
        $fileInfos = \iterator_to_array($finder->getIterator());
        foreach ($fileInfos as $fileInfo) {
            // replace __Name__ with $rectorName
            $newContent = $this->replaceNameVariable($rectorName, $fileInfo->getContents());
            $newFilePath = $this->replaceNameVariable($rectorName, $fileInfo->getRelativePathname());
            FileSystem::write(\getcwd() . '/' . $newFilePath, $newContent, null);
            $generatedFilePaths[] = $newFilePath;
        }
        $this->symfonyStyle->title('Generated files');
        $this->symfonyStyle->listing($generatedFilePaths);
        $this->symfonyStyle->success(\sprintf('Base for the "%s" rule was created. Now you can fill the missing parts', $rectorName));
        // 2. update autoload-dev in composer.json
        $composerJsonFilePath = \getcwd() . '/composer.json';
        if (\file_exists($composerJsonFilePath)) {
            $hasChanged = \false;
            $composerJson = JsonFileSystem::readFilePath($composerJsonFilePath);
            if (!isset($composerJson['autoload-dev']['psr-4']['Utils\\Rector\\'])) {
                $composerJson['autoload-dev']['psr-4']['Utils\\Rector\\'] = 'utils/rector/src';
                $composerJson['autoload-dev']['psr-4']['Utils\\Rector\\Tests\\'] = 'utils/rector/tests';
                $hasChanged = \true;
            }
            if ($hasChanged) {
                $this->symfonyStyle->success('We also update composer.json autoload-dev, to load Rector rules. Now run "composer dump-autoload" to update paths');
                JsonFileSystem::writeFile($composerJsonFilePath, $composerJson);
            }
        }
        return Command::SUCCESS;
    }
    private function replaceNameVariable(string $rectorName, string $contents) : string
    {
        return \str_replace('__Name__', $rectorName, $contents);
    }
    private function detectPHPUnitAttributeSupport() : bool
    {
        $composerJsonFilePath = \getcwd() . '/composer.json';
        if (!\file_exists($composerJsonFilePath)) {
            // be safe
            return \false;
        }
        $composerJson = JsonFileSystem::readFilePath($composerJsonFilePath);
        $phpunitVersion = $composerJson['require-dev']['phpunit/phpunit'] ?? null;
        if ($phpunitVersion === null) {
            return \false;
        }
        return (bool) Strings::match($phpunitVersion, self::START_WITH_10_REGEX);
    }
}
