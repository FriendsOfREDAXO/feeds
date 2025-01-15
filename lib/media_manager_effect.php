<?php
class rex_effect_feeds extends rex_effect_abstract
{
    public function execute()
    {
        $filename = $this->media->getMediaFilename();
        if (!$filename) {
            return;
        }

        // Check if this is a feeds file and extract the ID
        if (!preg_match('/^(\d+)\.feeds$/', $filename, $match)) {
            return;
        }
        $id = $match[1];

        // Get the item from database
        $sql = rex_sql::factory()
            ->setTable(rex_feeds_item::table())
            ->setWhere(['id' => $id, 'status' => 1])
            ->select('media_filename');

        if (!$sql->getRows()) {
            return;
        }
        
        $mediaFilename = $sql->getValue('media_filename');
        if (!$mediaFilename) {
            return;
        }

        // Set the media path to the feeds media file
        $mediaPath = rex_path::addonData('feeds', 'media/' . $mediaFilename);
        $this->media->setMediaPath($mediaPath);
    }

    public function getName()
    {
        return rex_i18n::msg('feeds_media_manager_effect');
    }
}
