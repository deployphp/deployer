<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Pimple\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * An attempt to perform an operation that requires a service identifier was made.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
class InvalidServiceIdentifierException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id The invalid identifier
     */
    public function __construct(string $id)
    {
        parent::__construct(\sprintf('Identifier "%s" does not contain an object definition.', $id));
    }
}
