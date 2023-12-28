<?php
/**
 * Autoloader: AutoLoader class
 *
 * @package Package_Name
 */

namespace YourPluginNamespace;

/**
 * A PSR-4 compliant AutoLoader class to load desired class files in the plugin.
 *
 * Usage:
 *
 *    $autoloader = new YourPluginNamespace\AutoLoader();
 *    $autoloader->register();
 *    $autoloader->add_namespace(
 *        'Namespace\\<SubNamespaces>',
 *        '.../path/to/base_directory'
 *    );
 *
 * A prefix for each desired Namespace and the corresponding paths must be added.
 *
 * @since 0.1.0
 */
final class AutoLoader {
	/**
	 * Associative array of Namespace prefix as the key and the corresponding paths for base directories as value.
	 *
	 * @since 0.1.0
	 * @var array
	 */
	private array $namespace_prefixes;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->namespace_prefixes = array();
	}

	/**
	 * Adds the namespace prefix with base directory to the autoloader.
	 *
	 * @since 0.1.0
	 *
	 * @param string $prefix The namespace Prefix.
	 * @param string $base_dir The base directory of the namespace.
	 * @return void
	 */
	public function add_namespace( string $prefix, string $base_dir ): void {
		// trim any leading or trailing backslashes and add trailing backslashes.
		$normalized_prefix = trim( $prefix, '\\' ) . '\\';

		// trim trailing separator and normalize it.
		$normalized_base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . '/';

		if ( false === isset( $this->namespace_prefixes[ $normalized_prefix ] ) ) {
			$this->namespace_prefixes[ $normalized_prefix ] = array();
		}

		array_push(
			$this->namespace_prefixes[ $normalized_prefix ],
			$normalized_base_dir
		);
	}

	/**
	 * Checks if the file exists, and then loads it.
	 *
	 * @param string $file_path The path to the file to be loaded.
	 * @return boolean true if the file is successfully loaded, false on failure.
	 */
	private function require_file( string $file_path ): bool {
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		require $file_path;
		return true;
	}

	/**
	 * Loads the mapped class file.
	 *
	 * @param string $namespace_prefix The namespace prefix of the class to load.
	 * @param string $relative_class_name The name of the class relative to the namespace prefix.
	 * @return string the mapped file's name on success, empty string on failure.
	 */
	private function load_mapped_file(
		string $namespace_prefix, string $relative_class_name
	): string {
		if ( false === isset(
			$this->namespace_prefixes[ $namespace_prefix ]
		) ) {
			return '';
		}

		foreach (
			$this->namespace_prefixes[ $namespace_prefix ]
			as $base_directory
		) {
			$file_path = $base_directory .
			str_replace( '\\', '/', $relative_class_name ) .
			'.php';

			if ( $this->require_file( $file_path ) ) {
				return $file_path;
			}
		}

		return '';
	}

	/**
	 * Loads the class file for the file name.
	 *
	 * @param string $class_name The fully-qualified class name.
	 * @return mixed Boolean false if the class file is not found, the path to the mapped file on success.
	 */
	public function load_class( string $class_name ): mixed {
		$position_of_backslashes = -1;

		$current_namespace_prefix = $class_name;

		while ( true ) {
			$position_of_backslashes = strrpos(
				$current_namespace_prefix,
				'\\'
			);

			if ( false === $position_of_backslashes ) {
				break;
			}

			$current_namespace_prefix = substr(
				$current_namespace_prefix,
				0,
				$position_of_backslashes + 1
			);

			$relative_class_name = substr(
				$class_name,
				$position_of_backslashes + 1
			);

			$mapped_file_path = $this->load_mapped_file(
				$current_namespace_prefix,
				$relative_class_name
			);

			if ( '' !== $mapped_file_path ) {
				return $mapped_file_path;
			}

			$current_namespace_prefix = rtrim(
				$current_namespace_prefix,
				'\\'
			);
		}

		return false;
	}

	/**
	 * Registers the autoloader with SPL autoloader stack.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'load_class' ) );
	}
}
