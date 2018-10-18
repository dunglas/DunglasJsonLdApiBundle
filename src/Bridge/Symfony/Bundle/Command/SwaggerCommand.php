<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Documentation\Documentation;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Console command to dump Swagger API documentations.
 *
 * @author Amrouche Hamza <hamza.simperfit@gmail.com>
 */
final class SwaggerCommand extends Command
{
    private $documentationNormalizer;
    private $resourceNameCollectionFactory;
    private $apiTitle;
    private $apiDescription;
    private $apiVersion;
    private $apiFormats;

    public function __construct(NormalizerInterface $documentationNormalizer, ResourceNameCollectionFactoryInterface $resourceNameCollection, string $apiTitle, string $apiDescription, string $apiVersion, array $apiFormats)
    {
        parent::__construct();

        $this->documentationNormalizer = $documentationNormalizer;
        $this->resourceNameCollectionFactory = $resourceNameCollection;
        $this->apiTitle = $apiTitle;
        $this->apiDescription = $apiDescription;
        $this->apiVersion = $apiVersion;
        $this->apiFormats = $apiFormats;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('api:swagger:export')
            ->setDescription('Dump the Swagger 2.0 (OpenAPI) documentation')
            ->addOption('yaml', 'y', InputOption::VALUE_NONE, 'Dump the documentation in YAML')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Write output to file');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentation = new Documentation($this->resourceNameCollectionFactory->create(), $this->apiTitle, $this->apiDescription, $this->apiVersion, $this->apiFormats);
        $data = $this->documentationNormalizer->normalize($documentation);
        $content = $input->getOption('yaml') ? Yaml::dump($data, 6, 4, Yaml::DUMP_OBJECT_AS_MAP) : json_encode($data, JSON_PRETTY_PRINT);
        if (!empty($input->getOption('output'))) {
            file_put_contents($input->getOption('output'), $content);
            $output->writeln(
                sprintf('Data written to %s', $input->getOption('output'))
            );
        } else {
            $output->writeln($content);
        }
    }
}
