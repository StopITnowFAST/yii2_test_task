<?php

use yii\db\Migration;

class m250409_200000_create_short_link_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%short_link}}', [
            'id' => $this->primaryKey(),
            'original_url' => $this->string(2048)->notNull(),
            'code' => $this->string(16)->notNull(),
            'click_count' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->createIndex('ux_short_link_code', '{{%short_link}}', 'code', true);
        $this->createIndex('idx_short_link_created_at', '{{%short_link}}', 'created_at');

        $this->createTable('{{%short_link_click}}', [
            'id' => $this->primaryKey(),
            'short_link_id' => $this->integer()->notNull(),
            'ip' => $this->string(45)->notNull(),
            'created_at' => $this->integer()->unsigned()->notNull(),
        ]);
        $this->createIndex('idx_short_link_click_link', '{{%short_link_click}}', 'short_link_id');
        $this->createIndex('idx_short_link_click_created', '{{%short_link_click}}', 'created_at');

        $this->addForeignKey(
            'fk_short_link_click_link',
            '{{%short_link_click}}',
            'short_link_id',
            '{{%short_link}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_short_link_click_link', '{{%short_link_click}}');
        $this->dropTable('{{%short_link_click}}');
        $this->dropTable('{{%short_link}}');
    }
}
