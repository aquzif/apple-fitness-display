<?php

namespace App\Services\AppleHealth;

use SimpleXMLElement;
use XMLReader;

class WorkoutXmlParser
{
    /** @var array<int, string> */
    protected array $skippedElements = ['WorkoutRoute'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseWorkoutsFromFile(string $path): array
    {
        $reader = new XMLReader();

        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new \RuntimeException('Unable to open Apple Health export.');
        }

        $workouts = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'Workout') {
                    $xml = $reader->readOuterXml();
                    if ($xml === false) {
                        continue;
                    }

                    $node = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
                    if (! $node instanceof SimpleXMLElement) {
                        continue;
                    }

                    $workouts[] = $this->convertElement($node);
                }
            }
        } finally {
            $reader->close();
        }

        return $workouts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseBodyMassRecordsFromFile(string $path): array
    {
        $reader = new XMLReader();

        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new \RuntimeException('Unable to open Apple Health export.');
        }

        $records = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Record') {
                    continue;
                }

                $type = $reader->getAttribute('type');
                if ($type !== 'HKQuantityTypeIdentifierBodyMass') {
                    continue;
                }

                $records[] = $this->collectAttributes($reader);
            }
        } finally {
            $reader->close();
        }

        return $records;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function parseHeartRateRecordsFromFile(string $path): array
    {
        $reader = new XMLReader();

        if (! $reader->open($path, null, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new \RuntimeException('Unable to open Apple Health export.');
        }

        $records = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Record') {
                    continue;
                }

                $type = $reader->getAttribute('type');
                if ($type !== 'HKQuantityTypeIdentifierHeartRate') {
                    continue;
                }

                $records[] = $this->collectAttributes($reader);
            }
        } finally {
            $reader->close();
        }

        return $records;
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectAttributes(XMLReader $reader): array
    {
        $attributes = [];

        if ($reader->moveToFirstAttribute()) {
            do {
                $attributes[$reader->name] = $reader->value;
            } while ($reader->moveToNextAttribute());

            $reader->moveToElement();
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function convertElement(SimpleXMLElement $element): array
    {
        $result = [];

        foreach ($element->attributes() as $name => $value) {
            $result[$name] = (string) $value;
        }

        foreach ($element->children() as $child) {
            $name = $child->getName();
            if (in_array($name, $this->skippedElements, true)) {
                continue;
            }

            $value = $this->convertElement($child);

            if (array_key_exists($name, $result)) {
                if (! is_array($result[$name]) || array_values($result[$name]) !== $result[$name]) {
                    $result[$name] = [$result[$name]];
                }

                $result[$name][] = $value;
                continue;
            }

            $result[$name] = $value;
        }

        $text = trim((string) $element);
        if ($text !== '') {
            $result['#text'] = $text;
        }

        return $result;
    }
}
