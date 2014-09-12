<?php

class GadgetRepoFactory {

	/**
	 * @var array[]
	 */
	private $repoConfigs = array();

	/**
	 * @var GadgetRepo[]
	 */
	private $repos = array();

	/**
	 * @var LocalGadgetRepo
	 */
	private $localRepo;

	/**
	 * @var GadgetRepoFactory
	 */
	private static $instance;

	/**
	 * @return GadgetRepoFactory
	 */
	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			global $wgGadgetRepositories;
			self::$instance = new self;
			foreach( $wgGadgetRepositories as $config ) {
				self::$instance->register( $config );
			}
		}

		return self::$instance;
	}

	public function register( array $config ) {
		$this->repos[] = $config;
	}

	/**
	 * @return LocalGadgetRepo
	 */
	public function getLocalRepo() {
		if ( $this->localRepo === null ) {
			$this->localRepo = new LocalGadgetRepo;
		}

		return $this->localRepo;
	}

	/**
	 * @return GadgetRepo[]
	 */
	public function getAllRepos() {
		if ( !$this->repos ) {
			foreach( $this->repoConfigs as $config ) {
				$class = $config['class'];
				unset( $config['class'] );
				$this->repos[] = new $class( $config );
			}

			$this->repos[] = $this->getLocalRepo();
		}

		return $this->repos;
	}

	/**
	 * Get all gadgets from all repositories
	 * @return Gadget[]
	 */
	public function getAllGadgets() {
		$retval = array();
		$repos = $this->getAllRepos();
		foreach ( $repos as $repo ) {
			$gadgets = $repo->getGadgetIds();
			foreach ( $gadgets as $id ) {
				// If there is a naming collision, let the first one win
				if ( !isset( $retval[$id] ) ) {
					$retval[$id] = $repo->getGadget( $id );
				}
			}
		}
		return $retval;
	}
}
