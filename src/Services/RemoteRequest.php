<?php
/**
 * Router Service Definition
 *
 * PHP Version 8.2
 *
 * @package github_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Services;

use MarkedEffect\GHPluginUpdater\Core\Abstracts,
    MarkedEffect\GHPluginUpdater\Processors\PluginHeaders;

use DI\Attribute\Inject;

/**
 * Service class for router actions
 *
 * @subpackage Services
 */

class RemoteRequest extends Abstracts\Module
{
    /**
     * Public constructor.
     *
     * @param PluginHeaders $plugin_header_processor The plugin header processor.
     * @param string        $github_user            The github user.
     * @param string        $github_repo            The github repo.
     * @param string        $branch                 The branch to use.
     * @param string        $plugin_file            The plugin file.
     * @param string        $package                The package name.
     */
    #[Inject([
        'github_user' => 'github.user',
        'github_repo' => 'github.repo',
        'branch'      => 'github.branch',
        'plugin_file' => 'config.file',
        'package'     => 'config.package',
    ])]
    public function __construct(
        protected PluginHeaders $plugin_header_processor,
        protected string $github_user = '',
        protected string $github_repo = '',
        protected string $branch = 'main',
        protected string $plugin_file = '',
        string $package = ''
    )
    {
        parent::__construct();
    }
    /**
     * Setter for the plugin branch.
     *
     * @param string $branch The plugin branch.
     * @return void
     */
    public function setBranch( string $branch ): void
    {
        $this->branch = $branch;
    }
    /**
     * Setter for the plugin user.
     *
     * @param string $user The plugin user.
     * @return void
     */
    public function setUser( string $user ): void
    {
        $this->github_user = $user;
    }
    /**
     * Setter for the plugin repo.
     *
     * @param string $repo The plugin repo.
     * @return void
     */
    public function setRepo( string $repo ): void
    {
        $this->github_repo = $repo;
    }
	/**
     * Request the remote info from the github repository.
     * 
     * Parses the plugin headers from the remote file, to compare against
     * the local file.
     *
     * @return array
     */
    public function getPluginInfo( $default = [] ): array
    {
        // $cached = wp_cache_get( 'remote_info', $this->package );

        // if ( $cached ) {
        //     return $cached;
        // }

        $request_url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            $this->github_user,
            $this->github_repo,
            $this->branch,
            $this->plugin_file
        );

        $response = wp_remote_get( $request_url );

        if ( is_wp_error( $response ) 
            || 200 !== wp_remote_retrieve_response_code( $response )
        ) {
            return apply_filters( "{$this->package}_default_plugin_headers", $default, $file );
        }

        $body = wp_remote_retrieve_body( $response );

        $plugin_headers = $this->plugin_header_processor->getFileData(
            $body
        );

        // $remote_info = $this->getFileData( $body, self::PLUGIN_HEADERS );

        // wp_cache_set( 'remote_info', $remote_info, $this->slug, HOUR_IN_SECONDS );

        return $plugin_headers;
    }
    /**
     * Request release data from the github repository.
     *
     * @param string $version
     *
     * @return object|null
     */
    public function requestRelease( string $version ): ?object
    {
        // $cached = wp_cache_get( "release_{$version}", $this->slug );

        // if ( $cached ) {
        //     return $cached;
        // }

        $request_url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/tags/%s',
            $this->github_user,
            $this->github_repo,
            $version
        );

        $response = wp_remote_get( $request_url );


        if ( is_wp_error( $response ) 
            || 200 !== wp_remote_retrieve_response_code( $response )
        ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );

        $release_info = json_decode( $body );

        // wp_cache_set( "release_{$version}", $release_info, $this->slug, HOUR_IN_SECONDS );

        return $release_info;
    }
    /**
     * Request the raw content of a file from the github repository.
     *
     * @param string $file The file to request.
     *
     * @return string|null
     */
    public function requestRawContent( string $file ): ?string
    {
        $request_url = sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            $this->github_user,
            $this->github_repo,
            $this->branch,
            $file
        );

        $response = wp_remote_get( $request_url );

        if ( is_wp_error( $response ) 
            || 200 !== wp_remote_retrieve_response_code( $response )
        ) {
            return '';
        }

        $body = wp_remote_retrieve_body( $response );

        return $body;
    }
}
