<?php
require __DIR__."/../AGitRepository.php";
require __DIR__."/../AGitBranch.php";
require __DIR__."/../AGitCommit.php";
require __DIR__."/../AGitException.php";
require __DIR__."/../AGitRemote.php";
require __DIR__."/../AGitTag.php";
class AGitRepositoryTest extends CTestCase {
	/**
	 * The path to the test repository
	 * @var string
	 */
	public $path = "/tmp/gitrepositorytest/";

	/**
	 * The git repository instance to use for testing
	 * @var AGitRepository
	 */
	protected $_repository;

	/**
	 * Holds a list of example files to add to the repository
	 * @var array
	 */
	protected $_files;
	/**
	 * Tests basic functionality
	 */
	public function testBasics() {
		$repo = $this->getRepository();
		$repo->add($this->getFiles()); // add all the files in one go

		$changedFiles = $repo->status();

		foreach($this->getFiles() as $file) {
			$this->assertTrue(isset($changedFiles[$file]));
		}

		$commitMessage = "Test Commit: ".uniqid();
		$response = $repo->commit($commitMessage); // commit our changes

		$this->assertTrue(is_array($response));
		foreach($this->getFiles() as $file) {
			$this->assertTrue(isset($response[$file])); // check our files were committed
		}

		$repo->rm($this->getFiles()); // delete our files
		foreach($this->getFiles() as $file) {
			$this->assertFalse(file_exists($this->path."/".$file)); // check our removal was successful
		}

		$commitMessage = "Test Commit: ".uniqid();
		$response = $repo->commit($commitMessage); // commit our deletions

		$this->assertTrue(is_array($response));

	}



	/**
	 * Tests the commit() method
	 */
	public function testCommit() {
		$repo = $this->getRepository();
		$repo->commit("test"); // make sure there are no hidden changes
		$this->assertFalse($repo->commit("test")); // no changes, should fail
		$files = $this->getFiles();
		foreach($files as $file) {
			$repo->add($this->path."/".$file);
			file_put_contents($this->path."/".$file,uniqid());
		}
		$commitMessage = "Test Commit: ".uniqid();
		$response = $repo->commit($commitMessage,true);

		$this->assertTrue(is_array($response));
		foreach($this->getFiles() as $file) {
			$this->assertTrue(isset($response[$file]));
		}
	}

	public function testCheckout() {
		$repo = $this->getRepository();
		$this->assertEquals("master",$repo->getActiveBranch()->name);
		$branchName = "test-branch-".uniqid();
		$repo->checkout($branchName,true);
		$this->assertEquals($branchName,$repo->getActiveBranch()->name);
		$repo->checkout("master");
		$this->assertEquals("master",$repo->getActiveBranch()->name);
	}

	public function testBranches() {
		$repo = $this->getRepository();
		foreach($repo->getBranches() as $branch) {
			$this->assertTrue($branch->name != "");
		}
		foreach($repo->getActiveBranch()->getCommits() as $commit) {
			$this->assertTrue($commit instanceof AGitCommit);
			$this->assertTrue(is_array($commit->getFiles()));
		}

	}

	public function testTags() {
		$repo = new AGitRepository();
		$repo->path = __DIR__."/../";
		$tag = new AGitTag("example-tag");
		$tag->message = "This is an example tag that should be deleted";
		$repo->getActiveBranch()->removeTag($tag);
		$this->assertTrue($repo->getActiveBranch()->addTag($tag) instanceof AGitTag);
		$this->assertTrue($tag->hash != "");
		$this->assertTrue($repo->getActiveBranch()->hasTag("example-tag"));
		foreach($repo->getTags() as $t) {
			$this->assertTrue($t->getCommit() instanceof AGitCommit);
		}
		$this->assertTrue($repo->getActiveBranch()->removeTag($tag));
	}

	public function testRemotes() {
		$repo = new AGitRepository();
		$repo->path = __DIR__."/../";
		foreach($repo->getRemotes() as $remote) {
			$this->assertTrue($remote->name != "");
			$this->assertGreaterThan(0,count($remote->getBranches()));
			foreach($remote->getBranches() as $branch) {
				$this->assertTrue($branch->name != "");
				$this->assertGreaterThan(0,count($branch->getCommits()));
			}
		}
	}

	/**
	 * Gets the repository to use for testing
	 * @return AGitRepository the respository for testing
	 */
	protected function getRepository() {
		if ($this->_repository === null) {
			$this->_repository = new AGitRepository();
			$this->_repository->setPath($this->path,true,true);
			$this->assertTrue(file_exists($this->path));
		}
		return $this->_repository;
	}

	/**
	 * Gets an array of filenames that should be added to git
	 * @return array
	 */
	protected function getFiles() {
		if ($this->_files === null) {
			$files = array(
				"test.txt" => uniqid(),
				"test2.txt" => uniqid(),
				"test3.txt" => uniqid(),
			);
			foreach($files as $file => $content) {
				file_put_contents($this->path."/".$file,$content);
				$this->assertTrue(file_exists($this->path."/".$file));
			}
			$this->_files = array_keys($files);
		}
		return $this->_files;
	}
}