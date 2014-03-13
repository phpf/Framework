<?php
/**
 * @package Phpf.Database
 * @subpackage Sql.Writer
 */

namespace Phpf\Database\Sql;

class Writer {
	
	public static function createTable( \Phpf\Database\Table\Schema $schema ){
		
		$sql = "CREATE TABLE {$schema->table} (";
		
		foreach ( $schema->columns as $name => $settings ) {
			$sql .= "\n  {$name} {$settings},";
		}
		
		$sql .= "\n  PRIMARY KEY  ({$schema->primary_key}),";
		
		if ( ! empty($schema->unique_keys) ) {
			foreach ( $schema->unique_keys as $name => $key ) {
				$sql .= "\n  UNIQUE KEY {$name} ({$key}),";
			}
		}
		
		if ( ! empty($schema->keys) ) {
			foreach ( $schema->keys as $name => $key ) {
				$sql .= "\n  KEY {$name} ({$key}),";
			}
		}
		
		$sql = trim($sql, ',') . "\n);";
		
		return $sql;
	}
	
	public static function dropTable( \Phpf\Database\Table\Schema $schema ){
	
		$sql = "DROP TABLE {$schema->table}";
		
		return $sql;
	}
	
}