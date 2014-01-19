<?php

require_once 'phing/TaskContainer.php';
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/git/GitBaseTask.php';

/**
 * Class BuildIndexTask
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 */
class GitCmsVersionsTask extends GitBaseTask implements TaskContainer {

	/**
	 * Current repository directory
	 *
	 * @var string
	 */
	private $repository;

	/**
	 * @var string
	 */
	private $build;

	/**
	 * Property name to set with output value from git-tag
	 *
	 * @var string
	 */
	private $outputProperty;

	/** Optional Vector holding the nested tasks */
	protected $nestedTasks = array();

	/**
	 * Add a nested task to Sequential.
	 *
	 * @param Task $nestedTask Nested task to execute Sequential
	 */
	public function addTask(Task $nestedTask) {
		$this->nestedTasks[] = $nestedTask;
	}

	/**
	 * @return mixed
	 */
	public function getBuild() {
		return $this->build;
	}

	/**
	 * @param string $build
	 * @return void
	 */
	public function setBuild($build) {
		$this->build = $build;
	}

	/**
	 * @param string $outputProperty
	 * @return void
	 */
	public function setOutputProperty($outputProperty) {
		$this->outputProperty = $outputProperty;
	}

	/**
	 * @return string
	 */
	public function getOutputProperty() {
		return $this->outputProperty;
	}

	/**
	 * Generates commands to download different sources related to TYPO3
	 *
	 * @return void
	 */
	public function main() {
		if (null === $this->getRepository()) {
			throw new BuildException('"repository" is required parameter');
		}

		$client = $this->getGitClient(FALSE, $this->getRepository());
		$command = $client->getCommand('show-ref');

		$this->log('git-show-ref command: ' . $command->createCommandString(), Project::MSG_INFO);

		try {
			$output = $command->execute();
		} catch (Exception $e) {
			$this->log($e->getMessage(), Project::MSG_ERR);
			throw new BuildException('Task execution failed. ' . $e->getMessage());
		}

		switch ($this->getBuild()) {
			case 'stable':
				$versions = $this->buildStable($output);
				break;
			case 'latest':
				$versions = $this->buildLatest($output);
				break;
			case 'dev':
				$versions = $this->buildDev($output);
				break;
			case 'master':
				$versions = $this->buildMaster($output);
				break;
			default:
				throw new BuildException('Unknown build parameter. [ stable | latest | dev | master ]', $e);
		}

		foreach ($versions as $version) {
			$this->getProject()->setProperty('build.commit', $version['commit']);
			$this->getProject()->setProperty('build.short', $version['short']);
			$this->getProject()->setProperty('build.version', $version['version']);
			$this->getProject()->setProperty('build.branch', $version['branch']);
			$this->getProject()->setProperty('build.name', $version['name']);

			foreach ($this->nestedTasks as $task) {
				$task->perform();
			}
		}
	}

	/**
	 * @param string $output
	 * @return array
	 */
	private function buildStable($output) {
		$this->log('Building stable');
		$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
		$versions = array();
		foreach (explode("\n", $output) AS $line) {
			if (preg_match($pattern, $line, $match)) {
				$versions[] = array(
					'commit' => $match[1],
					'short' => substr($match[1], 0, 7),
					'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
					'branch' => $match[2] . '.' . $match[3],
					'name' => $match[2] . '.' . $match[3] . '.' . $match[4]
				);
			} else {
				continue;
			}
		}

		return $versions;
	}

	/**
	 * @param string $output
	 * @return array
	 */
	private function buildLatest($output) {
		$this->log('Building latest');
		$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
		$temp = array();
		foreach (explode("\n", $output) AS $line) {
			if (preg_match($pattern, $line, $match)) {
				if ($temp[$match[2]][$match[3]]['latest'] < $match[4]) {
					$temp[$match[2]][$match[3]]['latest'] = $match[4];
					$temp[$match[2]][$match[3]][$match[4]] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
						'branch' => $match[2] . '.' . $match[3],
						'name' => $match[2] . '.' . $match[3] . 'latest'
					);
				}
			} else {
				continue;
			}
		}

		$versions = array();
		foreach ($temp AS $major) {
			foreach ($major AS $minor) {
				$versions[] = $minor[$minor['latest']];
			}
		}

		return $versions;
	}

	/**
	 * @param string $output
	 * @return array
	 */
	private function buildDev($output) {
		$this->log('Building dev');
		$pattern = '/(\S*)\srefs\/remotes\/origin\/TYPO3_([0-9]*)-([0-9]*)/';
		$versions = array();
		foreach (explode("\n", $output) AS $line) {
			if (preg_match($pattern, $line, $match)) {
				$versions[] = array(
					'commit' => $match[1],
					'short' => substr($match[1], 0, 7),
					'version' => $match[2] . '.' . $match[3] . '.0-dev',
					'branch' => $match[2] . '.' . $match[3],
					'name' => $match[2] . '.' . $match[3] . 'dev'
				);
			} else {
				continue;
			}
		}

		return $versions;
	}

	/**
	 * @param string $output
	 * @return array
	 */
	private function buildMaster($output) {
		$this->log('Building master');
		$pattern = '/(\S*)\srefs\/remotes\/origin\/master/';
		$versions = array();
		foreach (explode("\n", $output) AS $line) {
			if (preg_match($pattern, $line, $match)) {
				$versions[] = array(
					'commit' => $match[1],
					'short' => substr($match[1], 0, 7),
					'version' => 'master',
					'name' => 'master'
				);
			} else {
				continue;
			}
		}

		return $versions;
	}

	/**
	 * @param string $message
	 * @param $level
	 */
	public function log($message = '', $level = Project::MSG_INFO) {
		if (is_array($message)) {
			foreach ($message as $line) {
				$this->project->log('     [' . $this->taskName . '] ' . trim($line));
			}
		} else {
			$this->project->log('     [' . $this->taskName . '] ' . trim($message));
		}
	}

}

