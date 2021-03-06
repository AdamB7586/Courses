<?php
namespace Courses\Tests;

use Courses\Videos;

class VideosTest extends SetUp
{
    protected $videos;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->videos = new Videos($this->db, $this->config);
    }
    
    public function tearDown(): void
    {
        parent::tearDown();
        $this->videos = null;
    }
    
    public function testExample()
    {
        $this->markTestIncomplete();
    }
}
