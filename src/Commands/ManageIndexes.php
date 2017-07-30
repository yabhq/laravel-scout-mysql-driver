<?php

namespace Yab\MySQLScout\Commands;

use Illuminate\Console\Command;
use Yab\MySQLScout\Services\IndexService;
use Illuminate\Contracts\Events\Dispatcher;
use Yab\MySQLScout\Events;

class ManageIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:mysql-index {model?} {--D|drop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MySQL FULLTEXT indexes for searchable models';

    protected $indexService;

    /**
     * Create a new command instance.
     *
     * @param IndexService $indexService
     */
    public function __construct(IndexService $indexService)
    {
        parent::__construct();
        $this->indexService = $indexService;
    }

    /**
     * Execute the console command.
     *
     * @param Dispatcher $events
     *
     * @return mixed
     */
    public function handle(Dispatcher $events)
    {
        $events->listen(Events\ModelIndexCreated::class, function ($event) {
            $this->comment("Index '$event->indexName' created with fields: $event->indexFields");
        });

        $events->listen(Events\ModelIndexUpdated::class, function ($event) {
            $this->comment("Index '$event->indexName' updated");
        });

        $events->listen(Events\ModelIndexDropped::class, function ($event) {
            $this->comment("Index '$event->indexName' dropped");
        });

        $events->listen(Events\ModelIndexIgnored::class, function ($event) {
            $this->comment("Existing Index '$event->indexName' ignored");
        });

        $model = $this->argument('model');
        $drop = $this->option('drop');

        if (!$model) {
            $modelDirectories = config('scout.mysql.model_directories');
            $searchableModels = $this->indexService->getAllSearchableModels($modelDirectories);

            foreach ($searchableModels as $searchableModel) {
                $drop ? $this->dropModelIndex($searchableModel) : $this->createOrUpdateModelIndex($searchableModel);
            }
        } else {
            $drop ? $this->dropModelIndex($model) : $this->createOrUpdateModelIndex($model);
        }
    }

    private function createOrUpdateModelIndex($searchableModel)
    {
        $this->info("Creating index for $searchableModel...");
        $this->indexService->setModel($searchableModel);
        $this->indexService->createOrUpdateIndex();
    }

    private function dropModelIndex($searchableModel)
    {
        $this->info("Dropping index for $searchableModel...");
        $this->indexService->setModel($searchableModel);
        $this->indexService->dropIndex();
    }
}
