<?php
/**
 * PHP-DI definitions for the updater.
 *
 * @package github_plugin_updater
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 */

namespace Bmd\GithubWpUpdater;

return [
	Controller::class                              => \DI\autowire(),
	Processors\PluginHeaders::class               => \DI\autowire(),
	Processors\UpdateResponse::class              => \DI\autowire(),
	Providers\PluginInfo::class                   => \DI\autowire(),
	Providers\Updates::class                      => \DI\autowire(),
	Services\ReadmeParser::class                  => \DI\autowire(),
	Services\RemoteRequest::class                 => \DI\autowire(),
	\League\CommonMark\CommonMarkConverter::class => \DI\autowire(),
];
