<?php

namespace Addwiki\Wikibase\DataModel;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Wikibase\DataModel\Deserializers\DeserializerFactory;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\MediaInfo\DataModel\MediaInfoId;
use Wikibase\MediaInfo\DataModel\Serialization\MediaInfoDeserializer;
use Wikibase\MediaInfo\DataModel\Serialization\MediaInfoSerializer;

/**
 * @access public
 */
class DataModelFactory {

	private Deserializer $dataValueDeserializer;

	private Serializer $dataValueSerializer;

	public function __construct( Deserializer $dvDeserializer, Serializer $dvSerializer ) {
		$this->dataValueDeserializer = $dvDeserializer;
		$this->dataValueSerializer = $dvSerializer;
	}

	public function getDataValueDeserializer(): Deserializer {
		return $this->dataValueDeserializer;
	}

	public function getDataValueSerializer(): Serializer {
		return $this->dataValueSerializer;
	}

	private function newDefaultDataModelSerializerFactory(): SerializerFactory {
		return new SerializerFactory( $this->dataValueSerializer );
	}

	private function newDefaultDataModelDeserializerFactory(): DeserializerFactory {
        // Create our Factory, All services should be used through this!
        // You will need to add more or different datavalues here.
        // In the future Wikidata / Wikibase defaults will be provided in a separate
        // library.
        $dataValueClasses = [
            'unknown' => 'DataValues\UnknownValue',
            'string' => 'DataValues\StringValue',
            'boolean' => 'DataValues\BooleanValue',
            'number' => 'DataValues\NumberValue',
            'globecoordinate' => 'DataValues\Geo\Values\GlobeCoordinateValue',
            'monolingualtext' => 'DataValues\MonolingualTextValue',
            'multilingualtext' => 'DataValues\MultilingualTextValue',
            'quantity' => 'DataValues\QuantityValue',
            'time' => 'DataValues\TimeValue',
            'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
        ];

		return new DeserializerFactory(
			new DataValueDeserializer( $dataValueClasses ),
			$this->newEntityIdParser(),
			new InMemoryDataTypeLookup(),
			[],
			[],
		);
	}

	public function newEntityIdParser() {
		$builders = [
			// Defaults in all Wikibases
			ItemId::PATTERN => static function ( $serialization ) {
				return new ItemId( $serialization );
			},
			NumericPropertyId::PATTERN => static function ( $serialization ) {
				return new NumericPropertyId( $serialization );
			},
			// The MediaInfo extension
			MediaInfoId::PATTERN => static function ( $serialization ) {
				return new MediaInfoId( $serialization );
			},
		];
		return new DispatchingEntityIdParser( $builders );
	}

	public function newEntityDeserializer(): Deserializer {
		$datamodelDeserializerFactory = $this->newDefaultDataModelDeserializerFactory();
		return new DispatchingDeserializer( [
			// Defaults in all Wikibases (Items and Properties)
			$datamodelDeserializerFactory->newEntityDeserializer(),
			// The MediaInfo extension
			new MediaInfoDeserializer(
				$datamodelDeserializerFactory->newEntityIdDeserializer(),
				$datamodelDeserializerFactory->newTermListDeserializer(),
				$datamodelDeserializerFactory->newStatementListDeserializer()
			),
		] );
	}

	public function newEntitySerializer(): Serializer {
		$datamodelSerializerFactory = $this->newDefaultDataModelSerializerFactory();
		return new DispatchingSerializer( [
			// Defaults in all Wikibases (Items and Properties)
			$datamodelSerializerFactory->newEntitySerializer(),
			// The MediaInfo extension
			new MediaInfoSerializer(
				$datamodelSerializerFactory->newTermListSerializer(),
				$datamodelSerializerFactory->newStatementListSerializer()
			),
		] );
	}

	public function newStatementDeserializer(): Deserializer {
		return $this->newDefaultDataModelDeserializerFactory()->newStatementDeserializer();
	}

	public function newStatementSerializer(): Serializer {
		return $this->newDefaultDataModelSerializerFactory()->newStatementSerializer();
	}

	public function newReferenceSerializer(): Serializer {
		return $this->newDefaultDataModelSerializerFactory()->newReferenceSerializer();
	}

}
