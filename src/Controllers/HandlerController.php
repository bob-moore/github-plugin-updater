<?php
/**
 * Handler Controller
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob.moore@midwestfamilymadison.com>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://www.midwestfamilymadison.com
 * @since   1.0.0
 */

 namespace MarkedEffect\GHPluginUpdater\Controllers;

use MarkedEffect\GHPluginUpdater\Services\ServiceLocator,
	MarkedEffect\GHPluginUpdater\Handlers,
	MarkedEffect\GHPluginUpdater\Core\Interfaces,
	MarkedEffect\GHPluginUpdater\Core\Abstracts;

 /**
  * Controls the registration and execution of handlers
  *
  * @subpackage Controllers
  */
class HandlerController extends Abstracts\Controller
{
	/**
	 * Mounted context handler
	 *
	 * @var Interfaces\ContextHandler
	 */
	private ?Interfaces\ContextHandler $context_handler = null;
	/**
	 * Get definitions that should be added to the service container
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array
	{
		return [
			Handlers\Archive::class   => ServiceLocator::autowire(),
			Handlers\Search::class    => ServiceLocator::autowire(),
			Handlers\Blog::class      => ServiceLocator::autowire(),
			Handlers\Single::class    => ServiceLocator::autowire(),
			Handlers\Frontpage::class => ServiceLocator::autowire(),
			Handlers\Frontend::class  => ServiceLocator::autowire(),
			Handlers\Admin::class     => ServiceLocator::autowire(),
			'context.archive'         => ServiceLocator::get( Handlers\Archive::class ),
			'context.search'          => ServiceLocator::get( Handlers\Search::class ),
			'context.blog'            => ServiceLocator::get( Handlers\Blog::class ),
			'context.single'          => ServiceLocator::get( Handlers\Single::class ),
			'context.admin'           => ServiceLocator::get( Handlers\Admin::class ),
			'context.block-editor'    => ServiceLocator::get( Handlers\Admin::class ),
			'context.site-editor'     => ServiceLocator::get( Handlers\Admin::class ),
			'context.frontpage'       => ServiceLocator::get( Handlers\Frontend::class ),
			'context.frontend'        => ServiceLocator::get( Handlers\Frontend::class ),
		];
	}
	/**
	 * Mount actions for this class
	 *
	 * @return void
	 */
	public function mountActions(): void
	{
		add_action( "{$this->package}_dispatch_route_context", [ $this, 'mountContextHandler' ] );
	}
	/**
	 * Load a singular context handler
	 *
	 * @param string $name : string context name.
	 *
	 * @return void
	 */
	public function mountContextHandler( string $name ): void
	{
		if (
			is_object( $this->context_handler )
			&& is_a( $this->context_handler, Interfaces\ContextHandler::class )
		) {
			return;
		}

		$alias = 'context.' . strtolower( $name );

		$context_handler = ServiceLocator::locateService( $alias );

		if ( $context_handler && ! is_wp_error( $context_handler ) ) {
			$this->context_handler = $context_handler;
		}
	}
}
