<?php

/*
 * This file is part of the Pecserke YamlFixtures Bundle.
 *
 * (c) Tomas Pecserke <tomas.pecserke@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pecserke\YamlFixturesBundle\Parser;

use Pecserke\YamlFixturesBundle\Transformer\ObjectTransformerInterface;
use Pecserke\YamlFixturesBundle\Transformer\PropertyValueTransformerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class FixtureDataConfigurationTest extends TestCase {
    /**
     * @var FixtureDataConfiguration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp(): void {
        $this->processor = new Processor();
        $this->configuration = new FixtureDataConfiguration();
    }

    public function test_processConfiguration_usingAllSupportedOptions_processesCorrectly(): void {
        $config = Yaml::parse(<<< YaML
- class: Pecserke\YamlFixturesBundle\Parser\TestObject
  order: 1
  transformer: Pecserke\YamlFixturesBundle\Parser\TestObjectTransformer
  data:
    john_doe:
        firstName: John
        lastName: Doe
        birthDay:
            '@transformer': '@data_transformer.date_time'
            value: '1970-01-01 00:00:00'
        nameDay:
            '@transformer': 'Pecserke\YamlFixturesBundle\Parser\TestPropertyValueTransformer'
            value: '1970-01-01'
        roles: [ADMIN]
        department: ~

- class: \Pecserke\YamlFixturesBundle\Parser\AnotherTestObject
  order: 2
  transformer: '@object_transformer.test'
  equal_condition: 'x'

- class: \Pecserke\YamlFixturesBundle\Parser\AnotherTestObject
  order: 3
  equal_condition: ['x', 'y']
YaML
        );
        foreach (array_keys($config) as $key) {
            $config[$key]['file'] = 'test_file.yml';
        }

        $expected = [
            [
                'class' => 'Pecserke\YamlFixturesBundle\Parser\TestObject',
                'order' => 1,
                'equal_condition' => [],
                'transformer' => 'Pecserke\YamlFixturesBundle\Parser\TestObjectTransformer',
                'data' => [
                    [
                        '@reference' => 'john_doe',
                        'properties' => [
                            'firstName' => 'John',
                            'lastName' => 'Doe',
                            'birthDay' => [
                                '@transformer' => '@data_transformer.date_time',
                                'value' => '1970-01-01 00:00:00'
                            ],
                            'nameDay' => [
                                '@transformer' => 'Pecserke\YamlFixturesBundle\Parser\TestPropertyValueTransformer',
                                'value' => '1970-01-01'
                            ],
                            'roles' => ['ADMIN'],
                            'department' => null
                        ]
                    ]
                ],
                'file' => 'test_file.yml'
            ],
            [
                'class' => '\Pecserke\YamlFixturesBundle\Parser\AnotherTestObject',
                'order' => 2,
                'equal_condition' => ['x'],
                'transformer' => '@object_transformer.test',
                'data' => [],
                'file' => 'test_file.yml'
            ],
            [
                'class' => '\Pecserke\YamlFixturesBundle\Parser\AnotherTestObject',
                'order' => 3,
                'equal_condition' => ['x', 'y'],
                'transformer' => null,
                'data' => [],
                'file' => 'test_file.yml'
            ]
        ];

        $processedConfiguration = $this->processor->processConfiguration($this->configuration, [$config]);

        $this->assertEquals($expected, $processedConfiguration);
    }

    public function test_processConfiguration_withNonExistentFixtureClass_throwsException(): void {
        $config = Yaml::parse(<<< YaML
- class: This\Class\Does\Not\Exist
YaML
        );
        $config[0]['file'] = 'test_file.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "fixtures.0.class": Class "This\\\\Class\\\\Does\\\\Not\\\\Exist" does not exist');

        $this->processor->processConfiguration($this->configuration, [$config]);
    }

    public function test_processConfiguration_withNonExistentObjectTransformerClass_throwsException(): void {
        $config = Yaml::parse(<<< YaML
- class: Pecserke\YamlFixturesBundle\Parser\TestObject
  transformer: This\Class\Does\Not\Exist
YaML
        );
        $config[0]['file'] = 'test_file.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "fixtures.0.transformer": Invalid object transformer: "This\\\\Class\\\\Does\\\\Not\\\\Exist"');

        $this->processor->processConfiguration($this->configuration, [$config]);
    }
}

class TestObject {
}

class AnotherTestObject {
}

class TestObjectTransformer implements ObjectTransformerInterface {
    public function transform(array $data, string $className): object {
        throw new RuntimeException('not implemented');
    }
}

class TestPropertyValueTransformer implements PropertyValueTransformerInterface {
    public function transform($value) {
        return null;
    }
}
