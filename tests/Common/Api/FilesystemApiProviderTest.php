<?php
namespace Aws\Test\Common\Api;

use Aws\Common\Api\FilesystemApiProvider;

/**
 * @covers Aws\Common\Api\FilesystemApiProvider
 */
class FilesystemApiProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testPathAndSuffixSetCorrectly()
    {
        $path = __DIR__ . '/';
        $p1 = new FilesystemApiProvider($path);
        $p2 = new FilesystemApiProvider($path, true);

        $this->assertEquals(__DIR__, $this->readAttribute($p1, 'path'));
        $this->assertEquals(
            '.normal.json',
            $this->readAttribute($p1, 'apiSuffix')
        );
        $this->assertEquals(
            '.normal.min.json',
            $this->readAttribute($p2, 'apiSuffix')
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage File not found:
     */
    public function testEnsuresFileExists()
    {
        $p = new FilesystemApiProvider(__DIR__);
        $p->getService('foo', '2010-20-04');
    }

    public function testEnsuresValidJson()
    {
        $path = sys_get_temp_dir() . '/invalid-2010-12-05.normal.json';
        file_put_contents($path, 'foo, bar');
        $p = new FilesystemApiProvider(sys_get_temp_dir());
        try {
            $p->getService('invalid', '2010-12-05');
            $this->fail('Did not throw');
        } catch (\InvalidArgumentException $e) {
            unlink($path);
        }
    }

    public function testReturnsServiceNames()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $this->assertEquals(['dynamodb', 'ec2'], $p->getServiceNames());
    }

    public function testRetrievesServiceVersions()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $this->assertEquals(
            ['2011-12-05', '2012-08-10'],
            $p->getServiceVersions('dynamodb')
        );
    }

    public function testReturnsLatestServiceData()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $this->assertEquals(
            ['foo' => 'bar'],
            $p->getService('dynamodb', 'latest')
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage There are no versions of the dodo service available
     */
    public function testThrowsWhenNoLatestVersionIsAvailable()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $p->getService('dodo', 'latest');
    }

    public function testReturnsPaginatorConfigs()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $result = $p->getServicePaginatorConfig('dynamodb', 'latest');
        $this->assertEquals(['abc' => '123'], $result);
        $result = $p->getServicePaginatorConfig('dynamodb', '2011-12-05');
        $this->assertEquals([], $result);
    }

    public function testReturnsWaiterConfigs()
    {
        $p = new FilesystemApiProvider(__DIR__ . '/api_provider_fixtures');
        $result = $p->getServiceWaiterConfig('dynamodb', 'latest');
        $this->assertEquals(['abc' => '456'], $result);
        $result = $p->getServiceWaiterConfig('dynamodb', '2011-12-05');
        $this->assertEquals([], $result);
    }
}