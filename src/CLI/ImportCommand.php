<?php
/**
 * CLI Import Command class.
 *
 * WP-CLI command for importing markdown content to posts.
 *
 * @package CrispyTheme
 * @since 1.0.0
 */

declare(strict_types=1);

namespace CrispyTheme\CLI;

use CrispyTheme\Content\MarkdownRenderer;
use WP_CLI;
use WP_CLI_Command;

/**
 * Manage CrispyTheme markdown content.
 *
 * ## EXAMPLES
 *
 *     # Import markdown from file to post
 *     $ wp crispy import 123 /path/to/content.md
 *
 *     # Export markdown from post to file
 *     $ wp crispy export 123 /path/to/output.md
 *
 *     # Clear markdown cache for a post
 *     $ wp crispy cache clear 123
 *
 *     # Clear all markdown cache
 *     $ wp crispy cache clear --all
 */
class ImportCommand extends WP_CLI_Command {

	/**
	 * Import markdown content from a file to a post.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to update.
	 *
	 * <file>
	 * : Path to the markdown file to import.
	 *
	 * [--force]
	 * : Overwrite existing markdown content without confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import markdown to post 123
	 *     $ wp crispy import 123 ./my-post.md
	 *     Success: Imported markdown content to post 123.
	 *
	 *     # Force import without confirmation
	 *     $ wp crispy import 123 ./my-post.md --force
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function import( array $args, array $assoc_args ): void {
		list( $post_id, $file ) = $args;
		$post_id                = (int) $post_id;
		$force                  = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post %d not found.', $post_id ) );
		}

		// Validate file exists and is readable.
		if ( ! file_exists( $file ) ) {
			WP_CLI::error( sprintf( 'File not found: %s', $file ) );
		}

		if ( ! is_readable( $file ) ) {
			WP_CLI::error( sprintf( 'File is not readable: %s', $file ) );
		}

		// Check for existing content.
		$existing = get_post_meta( $post_id, MarkdownRenderer::META_KEY, true );
		if ( ! empty( $existing ) && ! $force ) {
			WP_CLI::confirm(
				sprintf( 'Post %d already has markdown content. Overwrite?', $post_id )
			);
		}

		// Read the file.
		$markdown = file_get_contents( $file );
		if ( false === $markdown ) {
			WP_CLI::error( sprintf( 'Failed to read file: %s', $file ) );
		}

		// Update the post meta.
		$result = update_post_meta( $post_id, MarkdownRenderer::META_KEY, $markdown );

		if ( false === $result ) {
			WP_CLI::error( 'Failed to update post meta.' );
		}

		$word_count = str_word_count( $markdown );
		$char_count = mb_strlen( $markdown );

		WP_CLI::success(
			sprintf(
				'Imported markdown content to post %d (%d words, %d characters).',
				$post_id,
				$word_count,
				$char_count
			)
		);
	}

	/**
	 * Export markdown content from a post to a file.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : The ID of the post to export from.
	 *
	 * <file>
	 * : Path to the output file.
	 *
	 * [--force]
	 * : Overwrite existing file without confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export markdown from post 123
	 *     $ wp crispy export 123 ./my-post.md
	 *     Success: Exported markdown from post 123 to ./my-post.md
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function export( array $args, array $assoc_args ): void {
		list( $post_id, $file ) = $args;
		$post_id                = (int) $post_id;
		$force                  = WP_CLI\Utils\get_flag_value( $assoc_args, 'force', false );

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			WP_CLI::error( sprintf( 'Post %d not found.', $post_id ) );
		}

		// Get markdown content.
		$markdown = get_post_meta( $post_id, MarkdownRenderer::META_KEY, true );
		if ( empty( $markdown ) ) {
			WP_CLI::error( sprintf( 'Post %d has no markdown content.', $post_id ) );
		}

		// Check if file exists.
		if ( file_exists( $file ) && ! $force ) {
			WP_CLI::confirm( sprintf( 'File %s already exists. Overwrite?', $file ) );
		}

		// Write the file.
		$result = file_put_contents( $file, $markdown );
		if ( false === $result ) {
			WP_CLI::error( sprintf( 'Failed to write file: %s', $file ) );
		}

		WP_CLI::success(
			sprintf( 'Exported markdown from post %d to %s', $post_id, $file )
		);
	}

	/**
	 * Manage the markdown cache.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The cache action (clear, stats).
	 *
	 * [<post_id>]
	 * : The post ID (for clear action).
	 *
	 * [--all]
	 * : Clear all cache (for clear action).
	 *
	 * ## EXAMPLES
	 *
	 *     # Clear cache for a specific post
	 *     $ wp crispy cache clear 123
	 *
	 *     # Clear all markdown cache
	 *     $ wp crispy cache clear --all
	 *
	 *     # Show cache statistics
	 *     $ wp crispy cache stats
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function cache( array $args, array $assoc_args ): void {
		$action  = $args[0] ?? '';
		$post_id = isset( $args[1] ) ? (int) $args[1] : 0;
		$all     = WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );

		$cache = new \CrispyTheme\Cache\TransientCache();

		switch ( $action ) {
			case 'clear':
				if ( $all ) {
					$count = $cache->clear_all();
					WP_CLI::success( sprintf( 'Cleared %d cached entries.', $count ) );
				} elseif ( $post_id > 0 ) {
					$count = $cache->delete_for_post( $post_id );
					WP_CLI::success(
						sprintf( 'Cleared %d cached entries for post %d.', $count, $post_id )
					);
				} else {
					WP_CLI::error( 'Please specify a post ID or use --all flag.' );
				}
				break;

			case 'stats':
				$stats = $cache->get_stats();
				WP_CLI::log( sprintf( 'Cached entries: %d', $stats['count'] ) );
				WP_CLI::log( sprintf( 'Total size: %s', size_format( $stats['total_size'] ) ) );
				break;

			default:
				WP_CLI::error( 'Unknown action. Use "clear" or "stats".' );
		}
	}

	/**
	 * Bulk import markdown files to posts.
	 *
	 * ## OPTIONS
	 *
	 * <directory>
	 * : Directory containing markdown files.
	 *
	 * [--post-type=<type>]
	 * : Post type to create. Default: post.
	 *
	 * [--status=<status>]
	 * : Post status. Default: draft.
	 *
	 * [--author=<id>]
	 * : Author ID for new posts.
	 *
	 * [--dry-run]
	 * : Show what would be imported without making changes.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import all .md files from a directory
	 *     $ wp crispy bulk-import ./content/
	 *
	 *     # Import as pages with publish status
	 *     $ wp crispy bulk-import ./pages/ --post-type=page --status=publish
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function bulk_import( array $args, array $assoc_args ): void {
		$directory = rtrim( $args[0], '/' );
		$post_type = $assoc_args['post-type'] ?? 'post';
		$status    = $assoc_args['status'] ?? 'draft';
		$author    = isset( $assoc_args['author'] ) ? (int) $assoc_args['author'] : get_current_user_id();
		$dry_run   = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		// Validate directory.
		if ( ! is_dir( $directory ) ) {
			WP_CLI::error( sprintf( 'Directory not found: %s', $directory ) );
		}

		// Find markdown files.
		$files = glob( $directory . '/*.md' );
		if ( empty( $files ) ) {
			WP_CLI::warning( 'No .md files found in directory.' );
			return;
		}

		WP_CLI::log( sprintf( 'Found %d markdown files.', count( $files ) ) );

		if ( $dry_run ) {
			WP_CLI::log( '(Dry run - no changes will be made)' );
		}

		$imported = 0;
		foreach ( $files as $file ) {
			$filename = basename( $file, '.md' );
			$title    = ucwords( str_replace( [ '-', '_' ], ' ', $filename ) );
			$markdown = file_get_contents( $file );

			if ( $dry_run ) {
				WP_CLI::log( sprintf( 'Would import: %s as "%s"', $file, $title ) );
				continue;
			}

			// Create the post.
			$post_id = wp_insert_post(
				[
					'post_title'  => $title,
					'post_type'   => $post_type,
					'post_status' => $status,
					'post_author' => $author,
				]
			);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( sprintf( 'Failed to create post for %s: %s', $file, $post_id->get_error_message() ) );
				continue;
			}

			// Add markdown content.
			update_post_meta( $post_id, MarkdownRenderer::META_KEY, $markdown );

			WP_CLI::log( sprintf( 'Imported %s to post %d', $file, $post_id ) );
			++$imported;
		}

		if ( ! $dry_run ) {
			WP_CLI::success( sprintf( 'Imported %d files.', $imported ) );
		}
	}
}
