<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Fabien Udriot <fabien.udriot@ecodev.ch>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class BuildIndexTask
 *
 * @author Jon Klixbüll Langeland <jon@moc.net>
 */
class BuildIndexTask extends Task {

	/**
	 * @var string
	 */
	private $source;

	/**
	 * @var string
	 */
	private $destination;

	/**
	 * @var array
	 */
	private $apigenConfig = array(
		'require' =>
			array(
				'min' => '2.8.0',
			),
		'resources' =>
			array(
				'resources' => 'resources',
			),
		'templates' =>
			array(
				'common' =>
					array(
						'overview.latte' => 'index.html',
						'combined.js.latte' => 'resources/combined.js',
						'elementlist.js.latte' => 'elementlist.js',
						'404.latte' => '404.html',
					),
				'optional' =>
					array(
						'sitemap' =>
							array(
								'filename' => 'sitemap.xml',
								'template' => 'sitemap.xml.latte',
							),
						'opensearch' =>
							array(
								'filename' => 'opensearch.xml',
								'template' => 'opensearch.xml.latte',
							),
						'robots' =>
							array(
								'filename' => 'robots.txt',
								'template' => 'robots.txt.latte',
							),
					),
				'main' =>
					array(
						'package' =>
							array(
								'filename' => 'package-%s.html',
								'template' => 'package.latte',
							),
						'namespace' =>
							array(
								'filename' => 'namespace-%s.html',
								'template' => 'namespace.latte',
							),
						'class' =>
							array(
								'filename' => 'class-%s.html',
								'template' => 'class.latte',
							),
						'constant' =>
							array(
								'filename' => 'constant-%s.html',
								'template' => 'constant.latte',
							),
						'function' =>
							array(
								'filename' => 'function-%s.html',
								'template' => 'function.latte',
							),
						'source' =>
							array(
								'filename' => 'source-%s.html',
								'template' => 'source.latte',
							),
						'tree' =>
							array(
								'filename' => 'tree.html',
								'template' => 'tree.latte',
							),
						'deprecated' =>
							array(
								'filename' => 'deprecated.html',
								'template' => 'deprecated.latte',
							),
						'todo' =>
							array(
								'filename' => 'todo.html',
								'template' => 'todo.latte',
							),
					),
			),
		'options' =>
			array(
				'elementDetailsCollapsed' => true,
				'elementsOrder' => 'natural',
			),
		'config' => '/Users/langeland/APIBuilder2/Resources/ApiGen/StyleSets/Bootstrap/config.neon',
	);

	/**
	 * @var array
	 */
	private $typeIndex = array(
		'' => 'Attribute',
		'' => 'Binding',
		'' => 'Callback',
		'' => 'Category',
		'c' => 'Class',
		'm' => 'Class',
		'mm' => 'Class',
		'p' => 'Class',
		'mp' => 'Class',
		'cc' => 'Class',
		'' => 'Command',
		'' => 'Component',
		'co' => 'Constant',
		'' => 'Constructor',
		'' => 'Define',
		'' => 'Delegate',
		'' => 'Directive',
		'' => 'Element',
		'' => 'Entry',
		'' => 'Enum',
		'' => 'Error',
		'' => 'Event',
		'' => 'Exception',
		'' => 'Field',
		'' => 'File',
		'' => 'Filter',
		'' => 'Framework',
		'f' => 'Function',
		'' => 'Global',
		'' => 'Guide',
		'' => 'Instance',
		'' => 'Instruction',
		'' => 'Interface',
		'' => 'Keyword',
		'' => 'Library',
		'' => 'Literal',
		'' => 'Macro',
		'm' => 'Method',
		'' => 'Mixin',
		'' => 'Module',
		'' => 'Namespace',
		'' => 'Notation',
		'' => 'Object',
		'' => 'Operator',
		'' => 'Option',
		'' => 'Package',
		'' => 'Parameter',
		'' => 'Property',
		'' => 'Protocol',
		'' => 'Record',
		'' => 'Sample',
		'' => 'Section',
		'' => 'Service',
		'' => 'Struct',
		'' => 'Style',
		'' => 'Tag',
		'' => 'Trait',
		'' => 'Type',
		'' => 'Union',
		'' => 'Value',
		'' => 'Variable'
	);

	/**
	 * @var array
	 */
	private $fileName = array(
		'c' => 'class',
		'co' => 'constant',
		'f' => 'function',
		'm' => 'class',
		'mm' => 'class',
		'p' => 'class',
		'mp' => 'class',
		'cc' => 'class'
	);

	/**
	 * @param string $destination
	 * @return void
	 */
	public function setDestination($destination) {
		$this->destination = $destination;
	}

	/**
	 * @return string
	 */
	public function getDestination() {
		return $this->destination;
	}

	/**
	 * @param string $source
	 * @return void
	 */
	public function setSource($source) {
		$this->source = $source;
	}

	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * Generates commands to download different sources related to TYPO3
	 *
	 * @return void
	 */
	public function main() {
		$elementList = file_get_contents($this->source);

		$this->log('Building index....');
		$this->log('Source: ' . $this->source);
		$this->log('Destination: ' . $this->destination);

		preg_match_all('~\[\K[^[\]]++~', $elementList, $rows);

		if (file_exists($this->destination)) {
			$this->log('Wiping destination file: ' . $this->destination);
			unlink($this->destination);
		}

		$this->log('Initialising database file: ' . $this->destination);
		$db = new SQLite3($this->destination);

		$db->exec('pragma synchronous = off;');

		$query = 'CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT);';
		$this->log('Creating database structure');
		$db->exec($query);

		$query = 'CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path);';
		$this->log('Creating unique index');
		$db->exec($query);

		$query = '';
		$counter = 0;
		foreach ($rows[0] as $row) {
			$data = explode(',', $row);
			$name = substr($data[1], 1, -1);
			$type = substr($data[0], 1, -1);

			$nameParts = explode('::', $name);

			$replace = array(
				'\\\\' => '.',
				'()' => ''
			);

			$file = sprintf(
				$this->apigenConfig['templates']['main'][$this->fileName[$type]]['filename'],
				str_replace(array_keys($replace), array_values($replace), $nameParts[0])
			);

			if (count($nameParts) > 1) {
				$file .= '#' . ('mm' === $type || 'mp' === $type ? 'm' : '') . preg_replace('/([\w]+)\(\)/', '_$1', $nameParts[1]);
			}

			$query .= vsprintf(
					'INSERT OR IGNORE INTO searchIndex(name, type, path) VALUES ("%s", "%s", "%s");',
					array(
						$name,
						$this->typeIndex[$type],
						$file
					)
				) . "\n";
			//$this->log('Inserting ' . $name . ' pointing at: ' . $file);

			$counter++;

			if ($counter % 100 == 0) {
				$this->log('Inserting on count: ' . $counter);
				$db->exec($query);
				$query = '';
			}


		}

		if ($query !== '') {
			$this->log('Inserting on count: ' . $counter);
			$db->exec($query);
		}

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

