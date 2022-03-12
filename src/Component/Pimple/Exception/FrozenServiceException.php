<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Pimple\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * An attempt to modify a frozen service was made.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
class FrozenServiceException extends \RuntimeException implements ContainerExceptionInterface
{
    /**
     * @param string $id Identifier of the frozen service
     */
    public function __construct(string $id)
    {
        parent::__construct(\sprintf('Cannot override frozen service "%s".', $id));
    }
}
