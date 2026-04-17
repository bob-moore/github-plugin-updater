<?php
/**
 * ServiceLocator Unit Tests
 *
 * PHP Version 8.2
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 *
 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator
 */

namespace Bmd\GithubWpUpdater\PHPUnit\Services;

use Bmd\GithubWpUpdater\PHPUnit\Traits\ClassInstanceTrait;
use WP_Mock\Tools\TestCase;
use Mockery;

/**
 * Test class for ServiceLocator Service
 *
 * @subpackage Tests\Services
 */
class ServiceLocatorTest extends TestCase {

	use ClassInstanceTrait;

	/**
	 * Test class constant pointing to the service being tested
	 *
	 * @var string
	 */
	protected const TEST_CLASS = 'Bmd\GithubWpUpdater\Services\ServiceLocator';

	/**
	 * Mock DI Container
	 *
	 * @var object
	 */
	private object $mock_container;

	/**
	 * Mock DI ContainerBuilder
	 *
	 * @var object
	 */
	private object $mock_builder;

	/**
	 * Set up test fixtures
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->mock_builder = Mockery::mock( 'DI\ContainerBuilder' );
		$this->mock_container = Mockery::mock( 'DI\Container' );

		// Mock ContainerBuilder methods
		$this->mock_builder
			->shouldReceive( 'useAutowiring' )
			->with( true )
			->andReturn( $this->mock_builder );

		$this->mock_builder
			->shouldReceive( 'useAttributes' )
			->with( true )
			->andReturn( $this->mock_builder );
	}

	/**
	 * Tears down the test fixtures
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		Mockery::close();
	}

	/**
	 * Tests that ServiceLocator initializes with ContainerBuilder
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::__construct
	 *
	 * @return void
	 */
	public function testConstructorInitializesContainerBuilder(): void {
		$locator = $this->getTestInstance();

		$this->assertInstanceOf(
			static::TEST_CLASS,
			$locator,
			'ServiceLocator should be instantiable'
		);
	}

	/**
	 * Tests adding a single service definition
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::addDefinition
	 *
	 * @return void
	 */
	public function testAddDefinitionWithSimpleDefinition(): void {
		$locator = $this->getTestInstance();

		$definition = [ 'SampleService' => 'value' ];

		$locator->addDefinition( 'SampleService', $definition );

		$reflection = new \ReflectionClass( $locator );
		$definitions_property = $reflection->getProperty( 'service_definitions' );
		$definitions_property->setAccessible( true );
		$definitions = $definitions_property->getValue( $locator );

		$this->assertNotEmpty(
			$definitions,
			'Service definitions should be populated'
		);

		$this->assertCount(
			1,
			$definitions,
			'Should have one definition'
		);
	}

	/**
	 * Tests adding multiple service definitions at once
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::addDefinitions
	 *
	 * @return void
	 */
	public function testAddDefinitionsWithMultipleDefinitions(): void {
		$locator = $this->getTestInstance();

		$definitions = [
			'Service1' => 'value1',
			'Service2' => 'value2',
			'Service3' => 'value3',
		];

		$locator->addDefinitions( $definitions );

		$reflection = new \ReflectionClass( $locator );
		$definitions_property = $reflection->getProperty( 'service_definitions' );
		$definitions_property->setAccessible( true );
		$stored_definitions = $definitions_property->getValue( $locator );

		$this->assertCount(
			3,
			$stored_definitions,
			'All three definitions should be added'
		);
	}

	/**
	 * Tests that getService returns WP_Error when container is not built
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::getService
	 *
	 * @return void
	 */
	public function testGetServiceReturnsErrorWhenContainerNotBuilt(): void {
		$locator = $this->getTestInstance();

		$result = $locator->getService( 'NonExistentService' );

		$this->assertInstanceOf(
			\WP_Error::class,
			$result,
			'Should return WP_Error when container not built'
		);

		$this->assertSame(
			'no_container_found',
			$result->get_error_code(),
			'Error code should be no_container_found'
		);
	}

	/**
	 * Tests that getService retrieves a service from the built container
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::getService
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::build
	 *
	 * @return void
	 */
	public function testGetServiceRetrievesServiceFromBuiltContainer(): void {
		$locator = $this->getTestInstance();

		// Mock the build process
		$this->mock_builder->shouldReceive( 'build' )
			->once()
			->andReturn( $this->mock_container );

		$this->mock_container->shouldReceive( 'get' )
			->with( 'TestService' )
			->andReturn( 'ServiceValue' );

		// Inject the mock builder
		$reflection = new \ReflectionClass( $locator );
		$builder_property = $reflection->getProperty( 'container_builder' );
		$builder_property->setAccessible( true );
		$builder_property->setValue( $locator, $this->mock_builder );

		// Build the container
		$locator->build();

		$result = $locator->getService( 'TestService' );

		$this->assertSame(
			'ServiceValue',
			$result,
			'Should retrieve service value from container'
		);
	}

	/**
	 * Tests that getService returns WP_Error when service not found
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::getService
	 *
	 * @return void
	 */
	public function testGetServiceReturnsErrorWhenServiceNotFound(): void {
		$locator = $this->getTestInstance();

		// Mock the build process and container error
		$exception = new \DI\NotFoundException( 'Service not found' );

		$this->mock_builder->shouldReceive( 'build' )
			->once()
			->andReturn( $this->mock_container );

		$this->mock_container->shouldReceive( 'get' )
			->with( 'NonExistent' )
			->andThrow( $exception );

		// Inject the mock builder
		$reflection = new \ReflectionClass( $locator );
		$builder_property = $reflection->getProperty( 'container_builder' );
		$builder_property->setAccessible( true );
		$builder_property->setValue( $locator, $this->mock_builder );

		// Build the container
		$locator->build();

		$result = $locator->getService( 'NonExistent' );

		$this->assertInstanceOf(
			\WP_Error::class,
			$result,
			'Should return WP_Error on NotFoundException'
		);
	}

	/**
	 * Tests mountService calls getService
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::mountService
	 *
	 * @return void
	 */
	public function testMountServiceCallsGetService(): void {
		$locator = $this->getTestInstance();

		// Mock container and builder
		$this->mock_builder->shouldReceive( 'build' )
			->andReturn( $this->mock_container );

		$this->mock_container->shouldReceive( 'get' )
			->with( 'MountableService' )
			->once()
			->andReturn( 'MountedValue' );

		$reflection = new \ReflectionClass( $locator );
		$builder_property = $reflection->getProperty( 'container_builder' );
		$builder_property->setAccessible( true );
		$builder_property->setValue( $locator, $this->mock_builder );

		$locator->build();
		$locator->mountService( 'MountableService' );

		$this->assertTrue( true );  // Verify mockery expectations were met
	}

	/**
	 * Tests makeService creates a new instance
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::makeService
	 *
	 * @return void
	 */
	public function testMakeServiceCreatesNewInstance(): void {
		$locator = $this->getTestInstance();

		$this->mock_builder->shouldReceive( 'build' )
			->andReturn( $this->mock_container );

		$this->mock_container->shouldReceive( 'make' )
			->with( 'Service', [ 'arg1', 'arg2' ] )
			->andReturn( null );

		$reflection = new \ReflectionClass( $locator );
		$builder_property = $reflection->getProperty( 'container_builder' );
		$builder_property->setAccessible( true );
		$builder_property->setValue( $locator, $this->mock_builder );

		$locator->build();

		$result = $locator->makeService( 'Service', [ 'arg1', 'arg2' ] );

		$this->assertNull(
			$result,
			'Should return null from container->make() on success'
		);
	}

	/**
	 * Tests makeService returns WP_Error when container not built
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::makeService
	 *
	 * @return void
	 */
	public function testMakeServiceReturnsErrorWhenContainerNotBuilt(): void {
		$locator = $this->getTestInstance();

		$result = $locator->makeService( 'Service' );

		$this->assertInstanceOf(
			\WP_Error::class,
			$result,
			'Should return WP_Error when container not built'
		);

		$this->assertSame(
			'no_container_found',
			$result->get_error_code(),
			'Error code should be no_container_found'
		);
	}

	/**
	 * Tests setService delegates to container
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::setService
	 *
	 * @return void
	 */
	public function testSetServiceDelegatesToContainer(): void {
		$locator = $this->getTestInstance();

		$this->mock_builder->shouldReceive( 'build' )
			->andReturn( $this->mock_container );

		$this->mock_container->shouldReceive( 'set' )
			->with( 'TestService', 'TestValue' )
			->once();

		$reflection = new \ReflectionClass( $locator );
		$builder_property = $reflection->getProperty( 'container_builder' );
		$builder_property->setAccessible( true );
		$builder_property->setValue( $locator, $this->mock_builder );

		$locator->build();
		$locator->setService( 'TestService', 'TestValue' );

		$this->assertTrue( true );  // Verify mockery expectations were met
	}

	/**
	 * Tests that autowire static method returns definition helper
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::autowire
	 *
	 * @return void
	 */
	public function testAutowireReturnsDefinitionHelper(): void {
		$result = $this->getTestClass()::autowire( 'TestClass' );

		$this->assertNotNull(
			$result,
			'autowire should return a non-null value'
		);
	}

	/**
	 * Tests that create static method returns definition helper
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::create
	 *
	 * @return void
	 */
	public function testCreateReturnsDefinitionHelper(): void {
		$result = $this->getTestClass()::create( 'TestClass' );

		$this->assertNotNull(
			$result,
			'create should return a non-null value'
		);
	}

	/**
	 * Tests that get static method returns reference
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::get
	 *
	 * @return void
	 */
	public function testGetReturnsReference(): void {
		$result = $this->getTestClass()::get( 'TestClass' );

		$this->assertNotNull(
			$result,
			'get should return a non-null value'
		);
	}

	/**
	 * Tests that factory static method returns definition helper
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::factory
	 *
	 * @return void
	 */
	public function testFactoryReturnsDefinitionHelper(): void {
		$factory = function() {
			return 'value';
		};

		$result = $this->getTestClass()::factory( $factory );

		$this->assertNotNull(
			$result,
			'factory should return a non-null value'
		);
	}

	/**
	 * Tests that decorate static method returns definition helper
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::decorate
	 *
	 * @return void
	 */
	public function testDecorateReturnsDefinitionHelper(): void {
		$decorator = function() {
			return 'value';
		};

		$result = $this->getTestClass()::decorate( $decorator );

		$this->assertNotNull(
			$result,
			'decorate should return a non-null value'
		);
	}

	/**
	 * Tests that string static method returns string definition
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::string
	 *
	 * @return void
	 */
	public function testStringReturnsStringDefinition(): void {
		$result = $this->getTestClass()::string( '{test}' );

		$this->assertNotNull(
			$result,
			'string should return a non-null value'
		);
	}

	/**
	 * Tests that value static method returns value definition
	 *
	 * @covers \Bmd\GithubWpUpdater\Services\ServiceLocator::value
	 *
	 * @return void
	 */
	public function testValueReturnsValueDefinition(): void {
		$result = $this->getTestClass()::value( 'test_value' );

		$this->assertNotNull(
			$result,
			'value should return a non-null value'
		);
	}
}
