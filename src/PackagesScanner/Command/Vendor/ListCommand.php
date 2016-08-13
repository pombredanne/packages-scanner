<?php
namespace IchHabRecht\PackagesScanner\Command\Vendor;

use IchHabRecht\PackagesScanner\Command\AbstractBaseCommand;
use IchHabRecht\PackagesScanner\Package\Repository as PackageRepository;
use IchHabRecht\PackagesScanner\Packagist\Repository as PackagistRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends AbstractBaseCommand
{
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
        parent::__construct($name, $packageRepository);
        $this->packagistRepository = $packagistRepository ?: new PackagistRepository();
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('vendor:list')
            ->setDescription('List vendor names')
            ->setHelp('This command checks the Packagist registration of the vendor names')
            ->addOption('only-registered', null, InputOption::VALUE_NONE, 'Show only registered vendor names')
            ->addOption('only-unregistered', null, InputOption::VALUE_NONE, 'Show only unregistered vendor names');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $onlyRegistered = $input->getOption('only-registered');
        $onlyUnregistered = $input->getOption('only-unregistered');

        $vendorNames = array_keys(
            $this->packageRepository->splitPackagesByVendor(
                $this->getPackagesFromRepository($input, $output)
            )
        );

        foreach ($vendorNames as $vendor) {
            $packages = $this->packagistRepository->findPackagesByVendor($vendor);
            $isRegistered = !empty($packages);

            if (!$onlyRegistered && !$onlyUnregistered
                || $onlyRegistered && $isRegistered
                || $onlyUnregistered && !$isRegistered
            ) {
                $output->writeln(' - ' . $vendor);
                $output->writeln('   - ' . ($isRegistered ? 'registered' : 'unregistered'));
            }
        }

        return 0;
    }
}
