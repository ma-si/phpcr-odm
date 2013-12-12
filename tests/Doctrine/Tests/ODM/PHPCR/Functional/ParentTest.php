<?php

namespace Doctrine\Tests\ODM\PHPCR\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\PHPCR\ChildrenCollection;
use Doctrine\ODM\PHPCR\DocumentManager;
use Doctrine\ODM\PHPCR\Mapping\Annotations as PHPCRODM;

/**
 * Verify the addition of child Documents to a parent
 * Document's collection.
 */
class ParentTest extends \Doctrine\Tests\ODM\PHPCR\PHPCRFunctionalTestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $this->dm = $this->createDocumentManager(array(__DIR__));
        $this->node = $this->resetFunctionalNode($this->dm);
        $this->dm->getPhpcrSession()->save();

        // create a parent for later use
        $parent = new ParentTestParentObj();
        $parent->parent = $this->dm->find(null, '/functional');
        $parent->name = 'ExistingParent';
        $this->dm->persist($parent);

        $this->dm->flush();

        // create one child for it; must flush first so the id exists
        $child = new ParentTestUnnamedChildObj();
        $child->parent = $parent;
        $this->dm->persist($child);

        $this->dm->flush();
        $this->dm->clear();
    }

    /**
     * Verify addition of child after retrieving Document
     * via the DocumentManager.
     */
    public function testExistingAddUnnamedChild()
    {
        $parent = $this->dm->find('Doctrine\Tests\ODM\PHPCR\Functional\ParentTestParentObj', '/functional/ExistingParent');

        // one child exists
        $this->assertCount(1, $parent->children);

        // the children collection has not yet been loaded
        $this->assertFalse($parent->children->isInitialized());

        // ParentTestUnnamedChildObj has only @ParentDocument present;
        // the id should be autogenerated in the form [parent]/[auto id]
        $child = new ParentTestUnnamedChildObj();
        $child->parent = $parent;
        $parent->children->add($child);
        $this->dm->persist($child);

        // the children collection was modified, so it should have initialized
        $this->assertTrue($parent->children->isInitialized());

        // one child was added and one was pre-existing
        $this->assertCount(2, $parent->children);

        $this->dm->flush();

        // check again after the flush
        $this->assertCount(2, $parent->children);
    }

    /**
     * Verify the creation of parent and subsequent addition
     * of a child whose ID will be generated by the system.
     */
    public function testCreateAndAddUnnamedChild()
    {
        $parent = new ParentTestParentObj();
        $parent->parent = $this->dm->find(null, '/functional');
        $parent->name = 'NewlyCreatedParentA';
        $this->dm->persist($parent);

        $this->dm->flush();

        // ParentTestParentObj has @ParentDocument and @NodeName fields
        // present; id should thus be [parent]/[nodename]
        $this->assertEquals('/functional/NewlyCreatedParentA', $parent->id);

        // ParentTestUnnamedChildObj has only @ParentDocument present;
        // the id should be autogenerated in the form [parent]/[auto id]
        $child = new ParentTestUnnamedChildObj();
        $child->parent = $parent;
        $parent->children->add($child);
        $this->dm->persist($child);

        $this->dm->flush();

        // one child was added
        $this->assertCount(1, $parent->children);
    }

    /**
     * Verify the creation of parent and subsequent addition
     * of a child whose ID is explicitly specified.
     */
    public function testCreateAndAddNamedChild()
    {
        $parent = new ParentTestParentObj();
        $parent->parent = $this->dm->find(null, '/functional');
        $parent->name = 'NewlyCreatedParentB';
        $this->dm->persist($parent);

        $this->dm->flush();

        // ParentTestParentObj has @ParentDocument and @NodeName fields
        // present; id should thus be [parent]/[nodename]
        $this->assertEquals('/functional/NewlyCreatedParentB', $parent->id);

        $child = new ParentTestNamedChildObj();
        $child->name = 'ExplicitChildName';
        $child->parent = $parent;
        $parent->children->add($child);
        $this->dm->persist($child);

        $this->dm->flush();

        // ParentTestNamedChildObj has @ParentDocument and @NodeName
        // fields present; id should be [parent]/[nodename]
        $this->assertEquals('/functional/NewlyCreatedParentB/ExplicitChildName', $child->id);

        // one child was added
        $this->assertCount(1, $parent->children);
    }

    /**
     * Verify the creation of parent and, without flushing, the subsequent
     * addition of a child whose ID is explicitly specified.
     */
    public function testCreateUnflushedAndAddNamedChild()
    {
        $parent = new ParentTestParentObj();
        $parent->parent = $this->dm->find(null, '/functional');
        $parent->name = 'NewlyCreatedParentC';
        $this->dm->persist($parent);

        $child = new ParentTestNamedChildObj();
        $child->name = 'ExplicitChildName';
        $child->parent = $parent;
        $parent->children->add($child);
        $this->dm->persist($child);

        $this->dm->flush();

        // ParentTestNamedChildObj has @ParentDocument and @NodeName
        // fields present; id should be [parent]/[nodename]
        $this->assertEquals('/functional/NewlyCreatedParentC/ExplicitChildName', $child->id);

        // one child was added
        $this->assertCount(1, $parent->children);
    }

}

/**
 * @PHPCRODM\Document
 */
class ParentTestParentObj
{
    /**
     * @PHPCRODM\Id
     */
    public $id;

    /**
     * @PHPCRODM\ParentDocument
     */
    public $parent;

    /**
     * @PHPCRODM\Nodename
     */
    public $name;

    /**
     * @PHPCRODM\Children
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}

/**
 * @PHPCRODM\Document
 */
class ParentTestUnnamedChildObj
{
    /**
     * @PHPCRODM\ParentDocument
     */
    public $parent;

    /**
     * @PHPCRODM\Id
     */
    public $id;
}

/**
 * @PHPCRODM\Document
 */
class ParentTestNamedChildObj
{
    /**
     * @PHPCRODM\ParentDocument
     */
    public $parent;

    /**
     * @PHPCRODM\Id
     */
    public $id;

    /**
     * @PHPCRODM\Nodename
     */
    public $name;
}
