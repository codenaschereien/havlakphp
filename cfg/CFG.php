<?php

class CFG {

  private $startNode = null;
  private $basicBlockMap = array();
  private $edgeList = array();

  public function createNode($name) {
    $node = null;

    if (!isset($this->basicBlockMap[$name]))  {
      $node = new BasicBlock($name);
      $this->basicBlockMap[$name] = $node;
    } else {
      $node = $this->basicBlockMap[$name];
    }

    if ($this->getNumNodes() == 1) {
      $this->startNode = $node;
    }

    return $node;
  }

  public function dump() {
    foreach ($this->basicBlockMap as $bb) {
      $bb->dump();
    }
  }

  public function addEdge(BasicBlockEdge $edge) {
    $this->edgeList[] = $edge;
  }

  public function getNumNodes() {
    return $this->numNodes;
  }

  public function getStartBasicBlock() {
    return $this->startNode;
  }

  public function getDst(BasicBlockEdge $edge) {
    return $edge->getDst();
  }

  public function getSrc(BasicBlockEdge $edge) {
    return $edge->getSrc();
  }

  public function getBasicBlocks() {
    return $this->basicBlockMap;
  }

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }
}