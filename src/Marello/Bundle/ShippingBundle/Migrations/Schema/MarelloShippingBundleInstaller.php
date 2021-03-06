<?php

namespace Marello\Bundle\ShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class MarelloShippingBundleInstaller implements Installation, AttachmentExtensionAwareInterface
{
    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createMarelloShipmentTable($schema);

        $this->attachmentExtension->addImageRelation(
            $schema,
            'marello_shipment', // entity table, e.g. oro_user, orocrm_contact etc.
            'shipping_label', // field name
            [], //additional options for relation
            20, // max allowed file size in megabytes, can be omitted, by default 1 Mb
            200, // thumbnail width in pixels, can be omitted, by default 32
            200 // thumbnail height in pixels, can be omitted, by default 32
        );
    }

    /**
     * Create marello_shipment table
     *
     * @param Schema $schema
     */
    protected function createMarelloShipmentTable(Schema $schema)
    {
        $table = $schema->createTable('marello_shipment');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('shippingService', 'string', ['length' => 255]);
        $table->addColumn('upsShipmentDigest', 'text', ['notnull' => false]);
        $table->addColumn('base64EncodedLabel', 'text', ['notnull' => false]);
        $table->addColumn('identificationNumber', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('upsPackageTrackingNumber', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Sets the AttachmentExtension
     *
     * @param AttachmentExtension $attachmentExtension
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }
}
