<?php

namespace Drupal\searchstax_flood\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\Event\ProcessingQueryEvent;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\search_api\SearchApiException;

class SearchStaxFloodSubscriber implements EventSubscriberInterface {

  protected $config;
  protected $flood;

  public function __construct(ConfigFactoryInterface $config_factory, FloodInterface $flood) {
    $this->config = $config_factory->get('searchstax_flood.settings');
    $this->flood = $flood;
  }

  public static function getSubscribedEvents() {
    return [
      'search_api.processing_query' => 'onQuery',
      'search_api.indexing_items' => 'onIndex',
    ];
  }

  /**
   * Identifies an outbound Search (Select) request.
   */
  public function onQuery(ProcessingQueryEvent $event) {
    $this->executeCheck($event->getQuery()->getIndex(), 'select');
  }

  /**
   * Identifies an outbound Indexing (Update) request.
   */
  public function onIndex(IndexingItemsEvent $event) {
    $this->executeCheck($event->getIndex(), 'update');
  }

  /**
   * Unified logic to block based on Server Backend ID.
   */
  protected function executeCheck($index, $type) {
    $server = $index->getServerInstance();

    // Check if the server uses the SearchStax backend.
    if (isset($configuration['connector']) && $configuration['connector'] === 'searchstax') {      $limit = $this->config->get($type . '_limit');
      $window = $this->config->get($type . '_window');

      if (!$this->flood->isAllowed('searchstax_flood.' . $type, $limit, $window)) {
        throw new SearchApiException("SearchStax flood protection: $type limit reached.");
      }

      $this->flood->register('searchstax_flood.' . $type, $window);
    }
  }
}
