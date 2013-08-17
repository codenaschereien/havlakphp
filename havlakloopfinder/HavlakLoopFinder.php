<?php

//======================================================
// Main Algorithm
//======================================================

/**
 * The Havlak loop finding algorithm.
 *
 */

/**
 * class HavlakLoopFinder
 *
 * This class encapsulates the complete finder algorithm
 */
class HavlakLoopFinder {

  private $cfg;      // Control Flow Graph
  private $lsg;      // Loop Structure Graph

  private static $maxMillis = 0;
  private static $minMillis = 2147483647;

  private static $nonBackPreds = array(); //new ArrayList<Set<Integer>>();
  private static $backPreds = array(); //new ArrayList<List<Integer>>();
  private static $number = null; //new HashMap<BasicBlock, Integer>();
  private static $maxSize = 0;
  private static $header = array();
  private static $type = array();
  private static $last = array(); //int[]
  private static $nodes = array();

  /**
   * enum BasicBlockClass
   *
   * Basic Blocks and Loops are being classified as regular, irreducible,
   * and so on. This enum contains a symbolic name for all these classifications
   */
  const BB_TOP          = 0; // uninitialized
  const BB_NONHEADER    = 1; // a regular BB
  const BB_REDUCIBLE    = 2; // reducible loop
  const BB_SELF         = 3; // single BB loop
  const BB_IRREDUCIBLE  = 4; // irreducible loop
  const BB_DEAD         = 5; // a dead BB
  const BB_LAST         = 6; // Sentinel

  //
  // Constants
  //
  // Marker for uninitialized nodes.
  const UNVISITED = 2147483647;

  // Safeguard against pathologic algorithm behavior.
  const MAXNONBACKPREDS = 32768; //(32 * 1024)

  public function __construct(CFG $cfgParm, LSG $lsgParm) {
    self::$number = new SplObjectStorage();
    $this->cfg = $cfgParm;
    $this->lsg = $lsgParm;
  }

  public function getMaxMillis() {
    return self::$maxMillis;
  }

  public function getMinMillis() {
    return self::$minMillis;
  }

  //
  // IsAncestor
  //
  // As described in the paper, determine whether a node 'w' is a
  // "true" ancestor for node 'v'.
  //
  // Dominance can be tested quickly using a pre-order trick
  // for depth-first spanning trees. This is why DFS is the first
  // thing we run below.
  //
  public function isAncestor($w, $v, array &$last) {
    return (($w <= $v) && ($v <= $last[$w]));
  }

  //
  // DFS - Depth-First-Search
  //
  // DESCRIPTION:
  // Simple depth first traversal along out edges with node numbering.
  //
  public function doDFS(BasicBlock $currentNode, array &$nodes, SplObjectStorage $number,
            array &$last, $current) {

    $nodes[$current]->initNode($currentNode, $current);
    $number->attach($currentNode, $current);

    $lastid = $current;
    foreach ($currentNode->getOutEdges() as $target) {
      if ($number->offsetGet($target) == self::UNVISITED) {
        $lastid = $this->doDFS($target, $nodes, $number, $last, $lastid + 1);
      }
    }
    $last[$number->offsetGet($currentNode)] = $lastid;
    return $lastid;
  }

  //
  // findLoops
  //
  // Find loops and build loop forest using Havlak's algorithm, which
  // is derived from Tarjan. Variable names and step numbering has
  // been chosen to be identical to the nomenclature in Havlak's
  // paper (which, in turn, is similar to the one used by Tarjan).
  //
  public function findLoops() {
    if ($this->cfg->getStartBasicBlock() == null) {
      return;
    }

    $startMillis = round(microtime(true) * 1000);
    $size = $this->cfg->getNumNodes();

    self::$nonBackPreds = array();
    self::$backPreds = array();
    self::$number->removeAll(self::$number);
    if ($size > self::$maxSize) {
      self::$header = array();
      self::$type = array();
      self::$last = array();
      self::$nodes = array();
      self::$maxSize = $size;
    }

    for ($i = 0; $i < $size; ++$i) {
      self::$nonBackPreds[] = array();
      self::$backPreds[] = array();
      self::$nodes[$i] = new UnionFindNode();
    }

    // Step a:
    //   - initialize all nodes as unvisited.
    //   - depth-first traversal and numbering.
    //   - unreached BB's are marked as dead.
    //
    foreach ($this->cfg->getBasicBlocks() as $bbIter) {
      self::$number->attach($bbIter, self::UNVISITED);
    }

    $this->doDFS($this->cfg->getStartBasicBlock(), self::$nodes, self::$number, self::$last, 0);

    // Step b:
    //   - iterate over all nodes.
    //
    //   A backedge comes from a descendant in the DFS tree, and non-backedges
    //   from non-descendants (following Tarjan).
    //
    //   - check incoming edges 'v' and add them to either
    //     - the list of backedges (backPreds) or
    //     - the list of non-backedges (nonBackPreds)
    //
    for ($w = 0; $w < $size; $w++) {
      self::$header[$w] = 0;
      self::$type[$w] = self::BB_NONHEADER;

      $nodeW = self::$nodes[$w]->getBb();
      if ($nodeW == null) {
        self::$type[$w] = self::BB_DEAD;
        continue;  // dead BB
      }

      if ($nodeW->getNumPred() > 0) {
        foreach ($nodeW->getInEdges() as $nodeV) {
          $v = self::$number->offsetGet($nodeV);
          if ($v == self::UNVISITED) {
            continue;  // dead node
          }

          if ($this->isAncestor($w, $v, self::$last)) {
            self::$backPreds[$w][] = $v;
          } else {
            self::$nonBackPreds[$w][] = $v;
          }
        }
      }
    }

    // Start node is root of all other loops.
    self::$header[0] = 0;

    // Step c:
    //
    // The outer loop, unchanged from Tarjan. It does nothing except
    // for those nodes which are the destinations of backedges.
    // For a header node w, we chase backward from the sources of the
    // backedges adding nodes to the set P, representing the body of
    // the loop headed by w.
    //
    // By running through the nodes in reverse of the DFST preorder,
    // we ensure that inner loop headers will be processed before the
    // headers for surrounding loops.
    //
    for ($w = $size - 1; $w >= 0; $w--) {
      // this is 'P' in Havlak's paper
      $nodePool = new SplObjectStorage(); //new LinkedList<UnionFindNode>();

      $nodeW = self::$nodes[$w]->getBb();
      if ($nodeW == null) {
        continue;  // dead BB
      }

      // Step d:
      foreach (self::$backPreds[$w] as $v) {
        if ($v != $w) {
          $nodePool->attach(self::$nodes[$v]->findSet());
        } else {
          self::$type[$w] = self::BB_SELF;
        }
      }

      // Copy nodePool to workList.
      //
      $workList = new SplDoublyLinkedList();
      foreach ($nodePool as $niter)
        $workList->push($niter);

      if (count($nodePool) != 0) {
        self::$type[$w] = self::BB_REDUCIBLE;
      }

      // work the list...
      //
      while (!$workList->isEmpty()) {
        $x = $workList->bottom();
        $workList->shift();

        // Step e:
        //
        // Step e represents the main difference from Tarjan's method.
        // Chasing upwards from the sources of a node w's backedges. If
        // there is a node y' that is not a descendant of w, w is marked
        // the header of an irreducible loop, there is another entry
        // into this loop that avoids w.
        //

        // The algorithm has degenerated. Break and
        // return in this case.
        //
        $nonBackSize = count(self::$nonBackPreds[$x->getDfsNumber()]);
        if ($nonBackSize > self::MAXNONBACKPREDS) {
          return;
        }

        foreach (self::$nonBackPreds[$x->getDfsNumber()] as $iter) {
          $y = self::$nodes[$iter];
          $ydash = $y->findSet();

          if (!$this->isAncestor($w, $ydash->getDfsNumber(), self::$last)) {
            self::$type[$w] = self::BB_IRREDUCIBLE;
            self::$nonBackPreds[$w][] = $ydash->getDfsNumber();
          } else {
            if ($ydash->getDfsNumber() != $w) {
              if (!$nodePool->contains($ydash)) {
                $workList->push($ydash);
                $nodePool->attach($ydash);
              }
            }
          }
        }
      }

      // Collapse/Unionize nodes in a SCC to a single node
      // For every SCC found, create a loop descriptor and link it in.
      //
      if (count($nodePool) > 0 || (self::$type[$w] == self::BB_SELF)) {
          $loop = $this->lsg->createNewLoop();

        $loop->setHeader($nodeW);
        $loop->setIsReducible(self::$type[$w] != self::BB_IRREDUCIBLE);

        // At this point, one can set attributes to the loop, such as:
        //
        // the bottom node:
        //    iter  = backPreds[w].begin();
        //    loop bottom is: nodes[iter].node);
        //
        // the number of backedges:
        //    backPreds[w].size()
        //
        // whether this loop is reducible:
        //    type[w] != BasicBlockClass.BB_IRREDUCIBLE
        //
        self::$nodes[$w]->setLoop($loop);

        foreach ($nodePool as $node) {
          // Add nodes to loop descriptor.
          self::$header[$node->getDfsNumber()] = $w;
          $node->union(self::$nodes[$w]);

          // Nested loops are not added, but linked together.
          if ($node->getLoop() != null) {
            $node->getLoop()->setParent($loop);
          } else {
            $loop->addNode($node->getBb());
          }
        }

        $this->lsg->addLoop($loop);
      }  // nodePool.size
    }  // Step c

    $totalMillis = round(microtime(true) * 1000) - $startMillis;

    if ($totalMillis > self::$maxMillis) {
      self::$maxMillis = $totalMillis;
    }
    if ($totalMillis < self::$minMillis) {
      self::$minMillis = $totalMillis;
    }
  }  // findLoops

  public function __get($name) {
    throw new Exception("Var $name does not exist!");
  }

}
