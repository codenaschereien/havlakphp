<?php
/**
* A simple class simulating the concept of Edges
* between Basic Blocks
*/

/**
* class BasicBlockEdga
*
* These data structures are stubbed out to make the code below easier
* to review.
*
* BasicBlockEdge only maintains two pointers to BasicBlocks.
*/
class BasicBlockEdge {

  private $from;
  private $to;

  public function getSrc() { return $this->from; }
  public function getDst() { return $this->to; }

  public function __construct(CFG $cfg, $fromName, $toName) {
    if (!is_int($fromName)) {
      throw new Exception('Invalid value given for parameter $fromName: ' . $fromName);
    }

    if (!is_int($toName)) {
      throw new Exception('Invalid value given for parameter $toName: ' . $toName);
    }

    $this->from = $cfg->createNode($fromName);
    $this->to = $cfg->createNode($toName);

    $this->from->addOutEdge($this->to);
    $this->to->addInEdge($this->from);

    $cfg->addEdge($this);
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }

}
