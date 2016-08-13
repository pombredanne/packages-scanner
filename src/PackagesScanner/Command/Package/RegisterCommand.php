<?php
namespace IchHabRecht\PackagesScanner\Command\Package;

use IchHabRecht\PackagesScanner\Package\Repository as PackageRepository;
use IchHabRecht\PackagesScanner\Packagist\Repository as PackagistRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RegisterCommand extends Command
{
    /**
     * @var PackageRepository
     */
    private $packageRepository;

    /**
     * @var PackagistRepository
     */
    private $packagistRepository;

    /**
     * @param string $name
     * @param PackageRepository $packageRepository
     * @param PackagistRepository $packagistRepository
     */
    public function __construct($name = null, PackageRepository $packageRepository = null, PackagistRepository $packagistRepository = null)
    {
        parent::__construct($name);
        $this->packageRepository = $packageRepository ?: new PackageRepository();
        $this->packagistRepository = $packagistRepository ?: new PackagistRepository();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this
            ->setName('package:register')
            ->setDescription('Register unregistered packages')
            ->setHelp('This command registers non-existing packages on Packagist')
            ->addArgument('repository-url', InputArgument::REQUIRED, 'The repository url to your packages.json file')
            ->addOption('exclude-vendor', null, InputOption::VALUE_OPTIONAL,
                'Comma separated list of vendor names to exclude');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repositoryUrl = $input->getArgument('repository-url');
        $excludeVendorNames = explode(',', $input->getOption('exclude-vendor'));
        array_walk($excludeVendorNames, 'trim');

        $output->writeln('Scanning packages at ' . $repositoryUrl);
        $output->writeln('');

        $packages = $this->packageRepository->splitPackagesByVendor(
            $this->packageRepository->findAllPackagesFromRepository($repositoryUrl)
        );

        foreach ($packages as $vendor => $vendorPackages) {
            if (in_array($vendor, $excludeVendorNames, true)) {
                continue;
            }

            $registeredPackageNames = $this->packagistRepository->findPackagesByVendor($vendor);
            foreach ($vendorPackages as $packageName => $package) {
                $isRegistered = in_array($vendor . '/' . $packageName, $registeredPackageNames, true);

                if ($isRegistered) {
                    continue;
                }

                $output->writeln(' - ' . $vendor . '/' . $packageName);
                $packageInformation = array_pop($package);
                $output->writeln('   - ' . $packageInformation['name']);
                $output->writeln('      - url: ' . ($packageInformation['source']['url'] ?? $packageInformation['dist']['url']));
                if (!empty($packageInformation['authors'])) {
                    foreach ($packageInformation['authors'] as $author) {
                        foreach ($author as $property => $value) {
                            $output->writeln('      - ' . $property . ': ' . $value);
                        }
                    }
                }
                $output->writeln('');
            }
        }

        return 0;
    }
}