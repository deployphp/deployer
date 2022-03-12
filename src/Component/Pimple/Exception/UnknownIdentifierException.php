<?php declare(strict_types=1);

/* (c) Anton Medvedev <anton@medv.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Component\Pimple\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * The identifier of a valid service or parameter was expected.
 *
 * @author Pascal Luna <skalpa@zetareticuli.org>
 */
class UnknownIdentifierException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    /**
     * @param string $id The unknown identifier
     */
    public function __construct(string $id)
    {
        parent::__construct(\sprintf('Identifier "%s" is not defined.', $id));
    }
}
