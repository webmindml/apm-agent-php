<?php

declare(strict_types=1);

namespace ElasticApmTests\ComponentTests\Util;

use Elastic\Apm\Impl\Config\RawSnapshotSourceInterface;
use Elastic\Apm\Impl\Log\Backend as LogBackend;
use Elastic\Apm\Impl\Log\LoggerFactory;
use ElasticApmTests\Util\LogSinkForTests;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AmbientContext
{
    /** @var self */
    private static $singletonInstance;

    /** @var string */
    private $dbgProcessName;

    /** @var LoggerFactory */
    private $loggerFactory;

    /** @var TestConfigSnapshot */
    private $testConfig;

    private function __construct(string $dbgProcessName)
    {
        $this->dbgProcessName = $dbgProcessName;
        $this->readAndApplyConfig(/* additionalConfigSource */ null);
    }

    public static function init(string $dbgProcessName): void
    {
        if (!isset(self::$singletonInstance)) {
            self::$singletonInstance = new AmbientContext($dbgProcessName);
        }

        if (self::testConfig()->appCodeHostKind === AppCodeHostKind::NOT_SET) {
            $optionName = AllComponentTestsOptionsMetadata::APP_CODE_HOST_KIND_OPTION_NAME;
            $envVarName = TestConfigUtil::envVarNameForTestOption($optionName);
            throw new RuntimeException(
                'Required configuration option ' . $optionName
                . " (environment variable $envVarName)" . ' is not set'
            );
        }

        if (!is_null(self::testConfig()->appCodePhpIni) && !file_exists(self::testConfig()->appCodePhpIni)) {
            $optionName = AllComponentTestsOptionsMetadata::APP_CODE_PHP_INI_OPTION_NAME;
            $envVarName = TestConfigUtil::envVarNameForTestOption($optionName);
            throw new RuntimeException(
                "Option $optionName (environment variable $envVarName)"
                . ' is set but it points to a file that does not exist: '
                . self::testConfig()->appCodePhpIni
            );
        }
    }

    public static function reconfigure(RawSnapshotSourceInterface $additionalConfigSource): void
    {
        TestCase::assertTrue(isset(self::$singletonInstance));
        self::$singletonInstance->readAndApplyConfig($additionalConfigSource);
    }

    private function readAndApplyConfig(?RawSnapshotSourceInterface $additionalConfigSource): void
    {
        $this->testConfig = TestConfigUtil::read($this->dbgProcessName, $additionalConfigSource);
        $this->loggerFactory = new LoggerFactory(
            new LogBackend(
                $this->testConfig->logLevel,
                new LogSinkForTests($this->dbgProcessName)
            )
        );
    }

    public static function dbgProcessName(): string
    {
        TestCase::assertTrue(isset(self::$singletonInstance));

        return self::$singletonInstance->dbgProcessName;
    }

    public static function testConfig(): TestConfigSnapshot
    {
        TestCase::assertTrue(isset(self::$singletonInstance));

        return self::$singletonInstance->testConfig;
    }

    public static function loggerFactory(): LoggerFactory
    {
        TestCase::assertTrue(isset(self::$singletonInstance));

        return self::$singletonInstance->loggerFactory;
    }
}
