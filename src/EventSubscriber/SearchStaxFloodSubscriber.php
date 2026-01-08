<?php

namespace Drupal\searchstax_flood\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Event\QueryPreExecuteEvent;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\SearchApiException;
use Drupal\search_api_solr\SolrBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;

class SearchStaxFloodSubscriber implements EventSubscriberInterface {

  protected $config;
  protected $flood;

  public function __construct(ConfigFactoryInterface $config_factory, FloodInterface $flood) {
    $this->config = $config_factory->get('searchstax_flood.settings');
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      SearchApiEvents::QUERY_PRE_EXECUTE => 'onQuery',
      SearchApiEvents::INDEXING_ITEMS => 'onIndex',
    ];
  }

  public function onQuery(QueryPreExecuteEvent $event) {
    $this->executeCheck($event->getQuery()->getIndex(), 'select');
  }

  public function onIndex(IndexingItemsEvent $event) {
    $this->executeCheck($event->getIndex(), 'update');
  }

  protected function executeCheck($index, $type) {
    $server = $index->getServerInstance();
    if (!$server) {
      return;
    }

    $backend = $server->getBackend();

    if ($backend instanceof SolrBackendInterface) {
      $configuration = $backend->getConfiguration();

      if (isset($configuration['connector']) && $configuration['connector'] === 'searchstax') {
        
        $limit = $this->config->get($type . '_limit');
        $window = $this->config->get($type . '_window');

        if (!$this->flood->isAllowed('searchstax_flood.' . $type, $limit, $window)) {
          throw new SearchApiException("SearchStax flood protection: $type limit reached.");
        }

        $this->flood->register('searchstax_flood.' . $type, $window);
      }
    }
  }
}