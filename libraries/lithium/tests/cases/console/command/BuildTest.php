<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\cases\console\command;

use \lithium\tests\mocks\console\command\MockBuild;
use \lithium\console\Request;
use \lithium\core\Libraries;

class BuildTest extends \lithium\test\Unit {

	public $request;

	protected $_backup = array();

	protected $_testPath = null;

	public function setUp() {
		$this->_backup['cwd'] = getcwd();
		$this->_backup['_SERVER'] = $_SERVER;
		$this->_backup['app'] = Libraries::get('app');

		$_SERVER['argv'] = array();
		$this->_testPath = LITHIUM_APP_PATH . '/resources/tmp/tests';

		Libraries::add('app', array('path' => $this->_testPath . '/new', 'bootstrap' => false));
		Libraries::add('build_test', array('path' => $this->_testPath . '/build_test'));
		$this->request = new Request(array('input' => fopen('php://temp', 'w+')));
		$this->request->params = array('library' => 'build_test');
	}

	public function tearDown() {
		$_SERVER = $this->_backup['_SERVER'];
		chdir($this->_backup['cwd']);
		Libraries::add('app', $this->_backup['app']);
		$this->_cleanUp();
	}

	public function testConstruct() {
		$build = new MockBuild(array('request' => $this->request));

		$expected = 'build_test';
		$result = $build->library;
		$this->assertEqual($expected, $result);
	}

	public function testSaveWithApp() {
		chdir($this->_testPath);
		$this->request->params = array('library' => 'app');
		$build = new MockBuild(array('request' => $this->request));
		$result = $build->save('test', array(
			'namespace' => 'app\tests\cases\models',
			'use' => 'app\models\Post',
			'class' => 'PostTest',
			'methods' => "\tpublic function testCreate() {\n\n\t}\n",
		));
		$this->assertTrue($result);

		$result = $this->_testPath . '/new/tests/cases/models/PostTest.php';
		$this->assertTrue(file_exists($result));

		$this->_cleanUp();
	}

	public function testSaveWithLibrary() {
		chdir($this->_testPath);
		$build = new MockBuild(array('request' => $this->request));
		$result = $build->save('test', array(
			'namespace' => 'build_test\tests\cases\models',
			'use' => 'build_test\models\Post',
			'class' => 'PostTest',
			'methods' => "\tpublic function testCreate() {\n\n\t}\n",
		));
		$this->assertTrue($result);

		$result = $this->_testPath . '/build_test/tests/cases/models/PostTest.php';
		$this->assertTrue(file_exists($result));

		$this->_cleanUp();
	}

	public function testRunWithoutCommand() {
		$build = new MockBuild(array('request' => $this->request));

		$expected = null;
		$result = $build->run();
		$this->assertEqual($expected, $result);
	}

	public function testRunWithModelCommand() {
		$build = new MockBuild(array('request' => $this->request));

		$this->request->params += array(
			'command' => 'build', 'action' => 'run', 'args' => array('model')
		);
		$build->run('model');

		$expected = 'model';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);
	}

	public function testRunWithTestModelCommand() {
		$this->request->params = array(
			'command' => 'build', 'action' => 'run',
			'args' => array('test', 'model', 'Post'),
			'library' => 'build_test'
		);
		$build = new MockBuild(array('request' => $this->request));

		$build->run('test', 'model');

		$expected = 'test';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/build_test/tests/cases/models/PostTest.php';
		$this->assertTrue(file_exists($result));
	}

	public function testRunWithTestOtherCommand() {
		$build = new MockBuild(array('request' => $this->request));
		$this->request->params = array(
			'command' => 'build', 'action' => 'run',
			'args' => array('test', 'something', 'Post'),
			'library' => 'build_test'
		);
		$build->run('test', 'something');

		$expected = 'test';
		$result = $build->request->params['command'];
		$this->assertEqual($expected, $result);

		$result = $this->_testPath . '/build_test/tests/cases/something/PostTest.php';
		$this->assertTrue(file_exists($result));
	}
}

?>