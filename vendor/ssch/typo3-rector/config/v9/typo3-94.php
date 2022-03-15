<?php

declare (strict_types=1);
namespace RectorPrefix20220315;

use Ssch\TYPO3Rector\Rector\v9\v4\AdditionalFieldProviderRector;
use Ssch\TYPO3Rector\Rector\v9\v4\BackendUtilityShortcutExistsRector;
use Ssch\TYPO3Rector\Rector\v9\v4\CallEnableFieldsFromPageRepositoryRector;
use Ssch\TYPO3Rector\Rector\v9\v4\ConstantsToEnvironmentApiCallRector;
use Ssch\TYPO3Rector\Rector\v9\v4\DocumentTemplateAddStyleSheetRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RefactorDeprecatedConcatenateMethodsPageRendererRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RefactorExplodeUrl2ArrayFromGeneralUtilityRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitMethodGraphicalFunctionsRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitMethodTemplateServiceRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitTemplateMethodCallRector;
use Ssch\TYPO3Rector\Rector\v9\v4\RemoveMethodsFromEidUtilityAndTsfeRector;
use Ssch\TYPO3Rector\Rector\v9\v4\SystemEnvironmentBuilderConstantsRector;
use Ssch\TYPO3Rector\Rector\v9\v4\TemplateGetFileNameToFilePathSanitizerRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseAddJsFileInsteadOfLoadJavascriptLibRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseClassSchemaInsteadReflectionServiceMethodsRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseContextApiForVersioningWorkspaceIdRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseContextApiRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseGetMenuInsteadOfGetFirstWebPageRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseLanguageAspectForTsfeLanguagePropertiesRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseRootlineUtilityInsteadOfGetRootlineMethodRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseSignalAfterExtensionInstallInsteadOfHasInstalledExtensionsRector;
use Ssch\TYPO3Rector\Rector\v9\v4\UseSignalTablesDefinitionIsBeingBuiltSqlExpectedSchemaServiceRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
return static function (\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $containerConfigurator) : void {
    $containerConfigurator->import(__DIR__ . '/../config.php');
    $services = $containerConfigurator->services();
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RefactorDeprecatedConcatenateMethodsPageRendererRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\CallEnableFieldsFromPageRepositoryRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\ConstantsToEnvironmentApiCallRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitTemplateMethodCallRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseContextApiRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RefactorExplodeUrl2ArrayFromGeneralUtilityRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\SystemEnvironmentBuilderConstantsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseContextApiForVersioningWorkspaceIdRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\DocumentTemplateAddStyleSheetRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseLanguageAspectForTsfeLanguagePropertiesRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\BackendUtilityShortcutExistsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseSignalTablesDefinitionIsBeingBuiltSqlExpectedSchemaServiceRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseGetMenuInsteadOfGetFirstWebPageRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitMethodGraphicalFunctionsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RemoveInitMethodTemplateServiceRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseAddJsFileInsteadOfLoadJavascriptLibRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseRootlineUtilityInsteadOfGetRootlineMethodRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\TemplateGetFileNameToFilePathSanitizerRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseSignalAfterExtensionInstallInsteadOfHasInstalledExtensionsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\UseClassSchemaInsteadReflectionServiceMethodsRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\RemoveMethodsFromEidUtilityAndTsfeRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\AdditionalFieldProviderRector::class);
    $services->set(\Ssch\TYPO3Rector\Rector\v9\v4\GeneralUtilityGetHostNameToGetIndpEnvRector::class);
};
