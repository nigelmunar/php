<?php

declare(strict_types=1);

function createNewFields(int $oldPageVersionID, int $newPageVersionID, PDO $pdo)
{

    $stmt = $pdo->prepare('
        SELECT `field_value_id` 
        FROM `field_values` 
        WHERE `page_version_id` = :old_page_version_id');

    $stmt->bindValue(':old_page_version_id', $oldPageVersionID, \PDO::PARAM_INT);

    $stmt->execute();

    $oldFieldValueID = $stmt->fetch()['field_value_id'];

    foreach ($stmt->fetchAll() as $row) {

        $stmt = $pdo->prepare('
            INSERT INTO field_values (`field_id`, `page_version_id`, `sort_order`)
            SELECT `field_id`, :new_page_version_id AS new_page_version_id, `sort_order`
            FROM `field_values` 
            WHERE `field_version_id` = :old_field_version_id ');

        $stmt->bindValue(':new_page_version_id', $newPageVersionID, \PDO::PARAM_INT);
        $stmt->bindValue(':old_field_version_id', $row['field_version_id'], \PDO::PARAM_INT);

        $stmt->execute();

        $newFieldValueID = $pdo->lastInsertId();

        $stmt = $pdo->prepare('
            INSERT INTO field_value_data (`field_value_id`, `language_id`, `field_data`) 
            SELECT :new_field_value_id AS new_field_value_id, `language_id`, `field_data` 
            FROM `field_value_data` 
            WHERE `field_value_id` = :old_field_value_id');

        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();


        $stmt = $pdo->prepare('
            INSERT INTO field_value_field_content_blocks (`field_value_id`, `field_content_block_id`) 
            SELECT :new_field_value_id AS new_field_value_id, `field_content_block_id` 
            FROM `field_value_field_content_blocks` 
            WHERE field_value_id = :old_field_value_id');

        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();

        $stmt = $pdo->prepare('
            INSERT INTO `field_value_media` (`field_value_id`, `media_id`, `language_id`) 
            SELECT :new_field_value_id AS new_field_value_id, `media_id`, `language_id` 
            FROM `field_value_media` 
            WHERE `field_value_id` = :old_field_value_id');

        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();

        $stmt = $pdo->prepare('
            INSERT INTO `field_value_page_links` (`field_value_id`, `page_id`) 
            SELECT :new_field_value_id AS new_field_value_id, `page_id` 
            FROM `field_value_page_links` 
            WHERE `field_value_id` = :old_field_value_id');

        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();

        $stmt = $pdo->prepare('
            INSERT INTO `field_value_relationships` (`parent_field_value_id`, `field_value_id`) 
            SELECT `parent_field_value_id`, :new_field_value_id AS new_field_value_id 
            FROM `field_value_relationships` 
            WHERE `field_value_id` = :old_field_value_id');


        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();

        $stmt = $pdo->prepare('
            INSERT INTO `field_value_relationships` (`parent_field_value_id`, `field_value_id`) 
            SELECT `:new_field_value_id AS `new_field_value_id`, `field_value_id` 
            FROM `field_value_relationships` 
            WHERE `parent_field_value_id` = :old_field_value_id');


        $stmt->bindValue(':new_field_value_id', $newFieldValueID);
        $stmt->bindValue(':old_field_value_id', $oldFieldValueID);

        $stmt->execute();
    }
}
