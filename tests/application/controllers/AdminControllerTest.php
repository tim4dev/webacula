<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

class AdminControllerTest extends ControllerTestCase
{
    /**
     * @access protected
     */
    protected function tearDown ()
    {
        session_write_close();
        parent::tearDown();
    }

    /**
     * Can't delete Role is used
     * @group admin
     * @group admin-role-in-use-delete
     */
    public function testRoleInUseDelete()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            'role_id' => 5
        ));
        $this->request->setMethod('POST');
        $this->dispatch('admin/role-delete');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('admin');
        $this->assertAction('role-index');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentContains('div', 'Exception : Can not delete. Role is used');
    }


    /**
     * Cascade delete Role
     * @group admin
     * @group admin-role-cascade-delete
     */
    public function testRoleCascadeDelete()
    {
        print "\n".__METHOD__.' ';
        $role_id = 6;
        $this->_rootLogin();
        $this->request->setPost(array(
            'role_id' => $role_id
        ));
        $this->request->setMethod('POST');
        $this->dispatch('admin/role-delete');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('admin');
        $this->assertAction('role-index');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        // check role
        Zend_Loader::loadClass('Wbroles');
        $table = new Wbroles();
        $row  = $table->fetchRow("id = $role_id");
        if ( $row != null )
                $this->assertTrue(FALSE, "\nRole delete fail!\n");
        unset($table);
        // check ACLs tables
        $arr_table = array(
            'WbCommandACL',
            'Wbresources',
            'WbStorageACL',
            'WbPoolACL',
            'WbClientACL',
            'WbFilesetACL',
            'WbJobACL',
            'WbWhereACL'
        );
        foreach ($arr_table as $tbl) {
            Zend_Loader::loadClass($tbl);
            $table = new $tbl();
            $row   = $table->fetchRow("role_id = $role_id");
            if ( $row != null )
                $this->assertTrue(FALSE, "\nRole cascade delete fail!\n");
            echo "0";
            unset($table);
        }
    }


    /**
     * Tabs
     * @group admin
     * @group admin-role-tabs
     */
    public function testRoleTabs()
    {
        print "\n".__METHOD__.' ';
        $this->_rootLogin();
        $this->request->setPost(array(
            'role_id'   => 5,
            'role_name' => 'user5_role',
            'order'     => '2',
            'name'      => __METHOD__
        ));
        $this->request->setMethod('POST');
        $this->dispatch('admin/client-add');
        $this->logBody( $this->response->outputBody() ); // debug log
        $this->assertController('admin');
        $this->assertAction('role-main-form');
        $this->assertNotQueryContentRegex('table', self::ZF_pattern); // Zend Framework
        $this->assertQueryContentContains('script', '{ selected: 2 }');
        // check Duplicate entry
        $this->dispatch('admin/client-add');
        $this->logBody( $this->response->outputBody(), 'a' ); // debug log
        /*
         * MySql :
         *      ...Integrity constraint violation...
         * PgSql :
         *      SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique
         *      constraint "webacula_client_acl_idx1"
         */
        $this->assertQueryContentRegex('div', '/Integrity constraint violation|Unique violation/i');
    }


}