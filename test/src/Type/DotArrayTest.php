<?php

/* (c) Anton Medvedev <anton@elfet.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Deployer\Type;

use Deployer\Type\DotArray;

/**
 * DotArrayTest class
 * 
 * @author OanhNN <oanhnn@rikkeisoft.com>
 * @version 1.0
 */
class DotArrayTest extends \PHPUnit_Framework_TestCase
{

    public function testDotArray()
    {
        $array     = [
            'abc1'      => [
                'xyz1' => 1,
                'xyz2' => [1, 2, 3],
            ],
            'abc2.xyz1' => 2,
            'abc2.xyz2' => [4, 5, 6],
            'callable'  => function() {
            echo 'hello world';
        },
            'abc.null' => null,
        ];

        $dotArray = new DotArray();
        foreach ($array as $key => $value) {
            $dotArray[$key] = $value;
        }

        foreach ($array as $key => $value) {
            $this->assertSame($value, $dotArray[$key]);
        }

        $this->assertSame(isset($array['abc9.xyz']), isset($dotArray['abc9.xyz']));
        $this->assertSame(isset($array['abc1']['xyz1']), isset($dotArray['abc1.xyz1']));
        
        $this->assertSame($dotArray['abc1']['xyz1'], $dotArray['abc1.xyz1']);
        $this->assertSame($array['abc2.xyz1'], $dotArray['abc2']['xyz1']);

        $this->assertSame(false, $dotArray->hasKey('abc1.null'));
        $this->assertSame(true, $dotArray->hasKey('abc1.xyz2'));
        $this->assertSame(true, $dotArray->hasKey('abc.null'));
        
        $this->assertSame(false, isset($dotArray['abc.null']));
        $this->assertSame(false, isset($dotArray['abc']['null']));

        $this->assertSame(['xyz1' => 2, 'xyz2' => [4, 5, 6]], $dotArray['abc2']);
        
        unset($dotArray['abc.null']);
        $this->assertSame(false, isset($dotArray['abc']['null']));
        $this->assertSame(false, $dotArray->hasKey('abc.null'));
        
        unset($dotArray['abc2']);
        $this->assertSame(false, isset($dotArray['abc2']['xyz1']));
        $this->assertSame(false, $dotArray->hasKey('abc2.xyz1'));
    }

}
