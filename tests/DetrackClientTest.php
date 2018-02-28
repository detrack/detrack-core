<?php

require_once "CreateClientTrait.php";

use PHPUnit\Framework\TestCase;
use Detrack\DetrackCore\Client\DetrackClient;
use Detrack\DetrackCore\Client\Exception\InvalidAPIKeyException;
use Detrack\DetrackCore\Model\Delivery;
use Detrack\DetrackCore\Factory\DeliveryFactory;

use Carbon\Carbon;
class DetrackClientTest extends TestCase
{
  protected $client;
  use CreateClientTrait;
  function setUp(){
    $this->createClient();
  }
  public function testBadKeys(){
    $this->expectException(InvalidAPIKeyException::class);
    $badClient = new DetrackClient("This is a bad key!");
    $badClient = new DetrackClient(NULL);
    $badClient = new DetrackClient(new \stdClass());
    $badClient = new DetrackClient(9001);
  }
  /**
  * Tests the bulkSaveDeliveries()'s create functionality in the DeliveryMiscActions traits
  *
  * @covers DeliveryFactory::create();
  * @covers DeliveryMiscActions::bulkSaveDeliveries();
  */
  public function testBulkCreateDeliveries(){
    $newFactory = new DeliveryFactory($this->client);
    $newDeliveries = $newFactory->createFakes(rand(101,201));
    //ensure nothing broke during the bulkSaveDeliveries call
    $this->assertTrue($this->client->bulkSaveDeliveries($newDeliveries));
    //now we try mixing create and update and see how it goes
    $newDeliveries2 = $newFactory->createFakes(rand(101,201));
    foreach($newDeliveries as $newDelivery){
      //modify some fields
      $newDelivery->instructions = "lorem ipsum bottom kek";
    }
    $combinedDeliveries = array_merge_recursive($newDeliveries,$newDeliveries2);
    shuffle($combinedDeliveries);
    //ensure nothing broke during the bulkSaveDeliveries call
    $this->assertTrue($this->client->bulkSaveDeliveries($combinedDeliveries));
    //sample one random delivery from $newDeliveries and $newDeliveries2
    $sampleDelivery = $newDeliveries[rand(0,count($newDeliveries)-1)];
    $sampleDelivery2 = $newDeliveries2[rand(0,count($newDeliveries2)-1)];
    //test if update worked
    $this->assertEquals("lorem ipsum bottom kek",$this->client->findDelivery($sampleDelivery->getIdentifier())->instructions);
    //test if create worked
    $this->assertNotNull($this->client->findDelivery($sampleDelivery2->getIdentifier()));
    return $combinedDeliveries;
  }
  /**
  * Tests the bulkFindDeliveries function to see if we can find the deliveries we just created
  *
  * @depends testBulkCreateDeliveries
  *
  * @covers DeliveryMiscActions::bulkFindDeliveries;
  */
  public function testBulkFindDeliveries($combinedDeliveries){
    //first test finding via delivery objects
    $this->assertEquals(count($combinedDeliveries),count($this->client->bulkFindDeliveries($combinedDeliveries)));
    //then test via delivery identifiers
    $combinedDeliveryIdentifiers = [];
    foreach($combinedDeliveries as $combinedDelivery){
      array_push($combinedDeliveryIdentifiers,$combinedDelivery->getIdentifier());
    }
    $this->assertEquals(count($combinedDeliveries),count($this->client->bulkFindDeliveries($combinedDeliveryIdentifiers)));
    return $combinedDeliveries;
  }
  /**
  * Test the findDeliveriesByDate function to see if we can list all deliveries created today
  *
  * @depends testBulkFindDeliveries
  *
  * @covers DeliveryMiscActions::findDeliveriesByDate
  */
  public function testFindDeliveriesByDate($combinedDeliveries){
    $receivedDeliveries = $this->client->findDeliveriesByDate($combinedDeliveries[0]->date);
    $this->assertInstanceOf(Delivery::class, $receivedDeliveries[rand(0,count($receivedDeliveries))]);
    $this->assertGreaterThanOrEqual(count($combinedDeliveries),count($receivedDeliveries));
    return $combinedDeliveries;
  }
}

 ?>
