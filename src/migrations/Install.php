<?php
/**
 * Created by PhpStorm.
 * User: leevigraham
 * Date: 25/2/17
 * Time: 20:52
 */

namespace bolden\htmlcache\migrations;

use Craft;
use craft\db\Migration;
/**
 * Installation Migration
 *
 * @author bolden
 */
class Install extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists('{{%htmlcache_caches%}}');
        $this->dropTableIfExists('{{%htmlcache_elements%}}');
        
        // create table caches
        $columns = [
            'id' => $this->primaryKey(),
            'siteId' => $this->integer()->notNull(),
            'uri' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ];
        $this->createTable('{{%htmlcache_caches%}}', $columns);
        $this->createIndex('htmlcache_caches_uri_siteId_idx', '{{%htmlcache_caches%}}', ['uri', 'siteId'], true);
        $this->addForeignKey('htmlcache_caches_siteId_fk', '{{%htmlcache_caches%}}', ['siteId'], '{{%sites%}}', ['id'], 'CASCADE');
        // die('a');

        // create table elements
        $columns = [
            'elementId' => $this->integer()->notNull(),
            'cacheId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid()
        ];
        $this->createTable('{{%htmlcache_elements%}}', $columns);
        $this->createIndex('htmlcache_caches_elementId_cacheId_idx', '{{%htmlcache_elements%}}', ['elementId', 'cacheId'], true);
        $this->addForeignKey('htmlcache_elements_elementId_fk', '{{%htmlcache_elements%}}', ['elementId'], '{{%elements%}}', ['id'], 'CASCADE');
        $this->addForeignKey('htmlcache_elements_cacheId_fk', '{{%htmlcache_elements%}}', ['cacheId'], '{{%htmlcache_caches%}}', ['id'], 'CASCADE');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTableIfExists('{{%htmlcache_elements%}}');
        $this->dropTableIfExists('{{%htmlcache_caches%}}');
    }
}
