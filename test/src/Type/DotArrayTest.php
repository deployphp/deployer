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

    public function testValidateKey()
    {
        $this->assertTrue(DotArray::validateKey('abc123'));
        $this->assertTrue(DotArray::validateKey('abc.xyz.null'));
        $this->assertTrue(DotArray::validateKey('abc_XYZ'));
        
        $this->assertFalse(DotArray::validateKey('abc-xyz'));
        $this->assertFalse(DotArray::validateKey('abc:xyz:123'));
        $this->assertFalse(DotArray::validateKey('abc@xyz.123'));
        
        $d = new DotArray();
        $this->setExpectedException('\RuntimeException', 'Key `abc-xyz` is invalid');
        $d['abc-xyz'] = 1;
    }

    public function testSet()
    {
        // test constructor
        $d0 = new DotArray(['one' => 1, 'two.other' => 2]);
        $this->assertSame(['one' => 1, 'two' => ['other' => 2]], $d0->toArray());

        // test set
        $d1 = new DotArray([]);
        $d1['one'] = 1;
        $this->assertEquals(['one' => 1], $d1->toArray());

        // test set override
        $d2 = new DotArray(['one' => 1]);
        $d2['one'] = 2;
        $this->assertEquals(['one' => 2], $d2->toArray());

        // test set path
        $d3 = new DotArray(['one' => ['two' => 1]]);
        $d3['one.two'] = 2;
        $this->assertEquals(['one' => ['two' => 2]], $d3->toArray());

        // test set path append
        $d4 = new DotArray(['one' => ['two' => 1]]);
        $d4['one.other'] = 1;
        $this->assertEquals(['one' => ['two' => 1, 'other' => 1]], $d4->toArray());

        // test set append
        $d5 = new DotArray(['one' => ['two' => 1]]);
        $d5['two'] = 2;
        $this->assertEquals(['one' => ['two' => 1], 'two' => 2], $d5->toArray());

        // test set override by array
        $d6 = new DotArray(['one' => ['two' => 1]]);
        $d6['one'] = ['other' => 3];
        $this->assertEquals(['one' => ['other' => 3]], $d6->toArray());

        // test set override and append
        $d7 = new DotArray(['one' => ['two' => 1]]);
        $d7['one'] = ['two' => 2, 'other' => 3];
        $this->assertEquals(['one' => ['two' => 2, 'other' => 3]], $d7->toArray());

        // test set override by array, this like dot array
        $d8 = new DotArray(['one' => ['two' => ['three' => 1]]]);
        $d8['one'] = ['two.other' => 3];
        $this->assertEquals(['one' => ['two' => ['other' => 3]]], $d8->toArray());
    }

    public function testGet()
    {
        $closures = function() {
            echo 'hello';
        };
        $obj = new \stdClass();
        $obj->id = 1;
        $obj->name = 'abc';
        
        $array = [
            'one' => [
                'two' => [
                    'three' => 1,
                ],
            ],
            'boolean' => true,
            'string' => 'abc',
            'array' => [1, 2, 3],
            'object' => $obj,
            'closures' => $closures,
            'null' => null,
        ];
        
        $d = new DotArray($array);
        
        // test toArray
        $this->assertSame($array, $d->toArray());
        
        // test get path
        $this->assertSame($array['one']['two'], $d['one.two']);
        $this->assertSame($array['one']['two']['three'], $d['one.two.three']);
        $this->assertSame($array['one']['two']['three'], $d['one.two']['three']);
        
        // test type of value
        foreach ($array as $key => $value) {
            $this->assertSame($value, $d[$key]);
        }
        
        // test get key not isset
        $this->assertSame(null, $d['one.two.three.next']);
    }

    public function testIssetUnsetAndHasKey()
    {
        $d = new DotArray();
        $d['abc.xyz'] = [
            'one' => 1, 
            'two' => 2, 
            'array' => [2012, '2013', 2014, '2015'],
            'null' => null
        ];
        
        $this->assertTrue(isset($d['abc.xyz']));
        $this->assertTrue($d->hasKey('abc.xyz'));
        
        $this->assertTrue(isset($d['abc']['xyz']['one']));
        $this->assertTrue(isset($d['abc.xyz.one']));
        $this->assertTrue(isset($d['abc.xyz']['one']));
        
        $this->assertTrue($d->hasKey('abc.xyz.two'));
        
        $this->assertTrue(isset($d['abc']['xyz']['array'][0]));
        $this->assertTrue(isset($d['abc']['xyz']['array'][1]));
        $this->assertTrue(isset($d['abc.xyz.array'][0]));
        $this->assertTrue($d->hasKey('abc.xyz.array.0'));
        
        $this->assertFalse(isset($d['abc']['xyz']['three']));
        $this->assertFalse(isset($d['abc.xyz.three']));
        $this->assertFalse(isset($d['abc.xyz']['three']));
        
        // Like isset(), a value of null is considered not set.
        $this->assertFalse(isset($d['abc.xyz.null']));
        $this->assertFalse(isset($d['abc']['xyz']['null']));
        // But has key
        $this->assertTrue($d->hasKey('abc.xyz.null'));
        
        unset($d['abc.xyz.one']);
        $this->assertFalse(isset($d['abc']['xyz']['one']));
        $this->assertFalse(isset($d['abc.xyz.one']));
        $this->assertFalse($d->hasKey('abc.xyz.one'));

        // key not set
        unset($d['abc.xyz.three']);
        $this->assertFalse($d->hasKey('abc.xyz.three'));
        
        unset($d['abc.xyz']);
        $this->assertFalse(isset($d['abc.xyz']));
        $this->assertFalse($d->hasKey('abc.xyz'));
        
        unset($d['abc.xyz.null']);
        $this->assertFalse($d->hasKey('abc.xyz.null'));
    }

}
