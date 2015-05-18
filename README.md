Etg24.EventSourcing
===================

This package provides basic CQRS/ES infrastructure for [TYPO3 Flow](http://flow.typo3.org).

Its purpose is to provide inspiration for writing your own customized set of tools for working with CQRS/ES. *I do not recommend using this package without understanding the underlaying concepts first.*

## Installation

```
$ composer require etg24/eventsourcing dev-master
```

## Commands<a name="commands"></a>

Commands define the interface to your domain model. They enter the *[command bus](#command_bus)* and will then be handled (sync/async) by *[command handlers](#command_handlers)*. Commands do never return any data, thus the interface for handling commands is of type `void`.

### Defining commands

You can define a command by extending from `Etg24\EventSourcing\Command\Command` (I will soonish update the code base to use the `CommandInterface` instead, but until then you need to inherit from `Command`). The command has a `commandId` that can be used for logging purposes, if so desired.

```php
<?php
namespace Vendor\Foo\Command;

use Etg24\EventSourcing\Command\Command;

/**
 * Any doc comment will be part of the CLI documentation!
 */
class AddInventoryItemToBasket extends Command {
    
    /**
     * @var string
     */
    public $basketId;

    /**
     * You will see this in the CLI!
     *
     * @var string
     */
    public $inventoryItemId;
    
    /**
     * @param string $basketId
     * @param string $inventoryItemId
     */
    public function __construct($basketId, $inventoryItemId) {
        parent::__construct();
        
        $this->basketId = $basketId;
        $this->inventoryItemId = $inventoryItemId;
    }

}

```

### Command handlers<a name="command_handlers"></a>

Command handlers execute commands coming from the command bus. They are part of your application layer and only orchestrate the domain model. Their job is to resolve dependencies and pass them to the domain model.

You can define a command handler by implementing the interface `Etg24\EventSourcing\Command\Handler\CommandHandlerInterface`. Alternatively, you can extend the `Etg24\EventSourcing\Command\CommandHandler` that will provide a default implementation for normal handlers.

The command handler provided by Etg24.EventSourcing has a naming convention for handler methods: `handle<CommandName>Command`. The command name is the simple class name. To avoid conflicts, it is furthermore necessary that the namespace for the command handler is `CommandHandler` and `Command` for the commands. See the example folder structure and implementation below.

```
- Vendor
  - Foo
    - CommandHandler
      - BasketCommandHandler.php
    - Command
      - AddInventoryItemToBasket.php
```

Here is the BasketCommandHandler implementation, handling the AddInventoryItemToBasket command.

```php
<?php
namespace Vendor\Foo\CommandHandler;

use Vendor\Foo\Command\AddInventoryItemToBasket;
use Vendor\Foo\Domain\Repository\BasketRepository;
use Etg24\EventSourcing\Command\CommandHandler;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class BasketCommandHandler extends CommandHandler {

    /**
     * @var BasketRepository
     * @Flow\Inject
     */
    protected $basketRepository;

    /**
     * @param AddInventoryItemToBasket $command
     */
    protected function handleAddInventoryItemToBasketCommand(AddInventoryItemToBasket $command) {
        // let's assume it exists
        $basket = $this->basketRepository->find($command->basketId);
        $basket->addInventoryItem($command->inventoryItemId);
        $this->basketRepository->save($basket);
    }

}

```

### The command bus<a name="command_bus"></a>

In order to get commands handled by their command handler, the command bus is used. You can inject the command bus in [event handlers](#event_handlers) or ActionControllers. The following example illustrates the usage within an ActionController.

```php
<?php
namespace Vendor\Foo\Controller;

// ... NS imports

class BasketController extends ActionController {

    /**
     * @var InternalCommandBus
     * @Flow\Inject
     */
    protected $commandBus;
    
    /**
     * @param string $basketId
     * @param string $inventoryItemId
     */
    public function addInventoryItemToBasketAction($basketId, $inventoryItemId) {
        // exceptions can and will be thrown here, catch accordingly
        // at least, until error handling is implemented
        $this->commandBus->handle(new AddInventoryItemToBasket(
            $basketId,
            $inventoryItemId
        ));
        
        // redirect
    }

}
```

Ideally, you would write a TypeConverter to automatically convert e. g. POST data to commands, handling them in a single ActionController.

### Working with the CLI

By default, all commands are exposed via the Flow CLI. Once defined, you can use

```
$ ./flow help
$ ./flow help foo:addinventoryitemtobasket
$ ./flow foo:addinventoryitemtobasket --basket-id="2" --inventory-item-id="1"
```

to view the required parameters for any given command and execute them.

To disable command CLI access, edit the `Settings.yaml` of your project like this:

```yaml
Etg24:
  EventSourcing:
    Command:
      Controller:
        enabled: false
```

Alternatively, you can *hide* the commands from normal CLI users by marking them as internal. That way the commands are still accessible but no longer printed by `./flow help`.

```yaml
Etg24:
  EventSourcing:
    Command:
      Controller:
        enabled: true
        markAsInternal: true
```

## Events<a name="events"></a>

Domain events are created by [aggregates](#aggregates), retrieved by [repositories](#repositories), stored in an [event store](#event_store) and published through the [event bus](#event_bus). They are the single source of truth in an event sourced model and define your models state.

### Defining events

An event is defined by extending from `Etg24\EventSourcing\Event\DomainEvent`. Please note that the constructor of the parent class must be called in order to generate the date when the event occurred (I'm still debating over doing this with e. g. AOP).

```php
<?php
namespace Vendor\Foo\Domain\Event;

use Etg24\EventSourcing\Event\DomainEvent;

class InventoryItemToBaskedAdded extends DomainEvent {
    
    /**
     * @var string
     */
    public $basketId;

    /**
     * @var string
     */
    public $inventoryItemId;
    
    /**
     * @param string $basketId
     * @param string $inventoryItemId
     */
    public function __construct($basketId, $inventoryItemId) {
        parent::__construct();
        
        $this->basketId = $basketId;
        $this->inventoryItemId = $inventoryItemId;
    }

}

```

### Repositories<a name="repositories"></a>

Repositories are used to save and retrieve aggregates. The naming convention from Flow also applies here. The `BasketRepository` for the aggregate `Basket` looks like this:

```php
namespace Vendor\Foo\Domain\Repository;

use Etg24\EventSourcing\Store\Repository;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class BasketRepository extends Repository {}
```

The default event store backend is the `EventStoreBackend` for [EventStore](https://geteventstore.com). You can implement the `StoreBackendInterface` and use the `Objects.yaml` to use your own implementations.

### The event bus<a name="event_bus"></a>

The event bus should normally only be used by the repository. I will add some interfaces soonish that enable the configuration for different queues, depending on whether events have to be handled asynchronously or synchronously.

## Event handlers<a name="event_handlers"></a>

Similar to [command handlers](#command_handlers), event handlers handle events that are published on the [event bus](#event_bus). Event handlers are subscribed to one or more [domain events](#events). To create an event handler and have it listen to events, implement the `EventHandlerInterface` (async) or `ImmediateEventHandlerInterface` (sync) interface. The [event bus](#event_bus) will then, depending on the interface, push events into a [queue](#queues) or directly pass them on for handling (will soon be done through different queues, API shouldn't change though).

Usually you would use event handlers for dealing with eventual consistency or things like sending emails. The following example illustrates the usage of the `AbstractEventHandler`, and its conventions.

### Defining an event handler

```php
<?php
namespace Vendor\Foo\Service;

// ... NS imports

class InformingCustomerAboutAddedInventoryItemService extends AbstractEventHandler implements ImmediateEventHandlerInterface {

    /**
     * @var array
     */
    protected $subscribedToEvents = [
        InventoryItemToBaskedAdded::class
    ];
    
    /**
     * @param InventoryItemToBaskedAdded $event
     */
    protected function handleInventoryItemToBaskedAddedEvent(InventoryItemToBaskedAdded $event) {
        // this is probably a bad idea to do, but certainly possible
        // needs some more checking though ;)
        $this->flashMessageContainer->addFlashMessage('Hi! The inventory item "' . $event->inventoryItemId . '" has been added to your basket.');
    }

}

```

## Aggregates<a name="aggregates"></a>

In event sourced environments, aggregates are reconstructed using domain events only! You can create an aggregate class by implementing `Etg24\EventSourcing\AggregateRootInterface`. The trait `Etg24\EventSourcing\AggregateSourcing` contains behaviour that will satisfy the interface and provides a sane default implementation for your aggregates.

### Instantiation

You can instantiate new aggregate instances like any other class in php. Please note that there is **NO** automatic identifier generation. The trait `AggregateSourcing` adds a property `$identifier` but does not fill it. When thinking about [commands](#commands), you will most likely want to generate the aggregates identifier before sending out an command.

```php
$basket = new Basket('123');
```

### Publishing new domain events

Once created, you can publish domain events. Domain events are only published from within an aggregate (or [entity](#entities)).

```php
public function addInventoryItem($inventoryItemId) {
    // business logic
    // validation logic
        
    $this->applyNewEvent(new InventoryItemToBaskedAdded($this->identifier, $inventoryItemId));
}
```

Now when saving this aggregate in a repository, the new events are written to the [event store](#event_store).

### Loading aggregates from an event stream

Normally, the [repository](#repositories) will handle the loading of existing aggregates for you. However, when testing you might want to load them manually. The trait `AggregateSourcing` implements the static method `loadFromEventStream` which will instantiate and apply events.

```php
$existingBasket = Basket::loadFromEventStream([
    new BasketCreated(123),
    new InventoryItemToBaskedAdded(123, 1)
]);
```

The trait will then instantiate a new instance of `Basket` without calling its constructor, then apply each event. To do so, you have to implement event applying methods. The convention for this is `on<EventName>`.

```php
/**
 * @param BasketCreated $event
 */
protected function onBasketCreated(BasketCreated $event) {
    $this->identifier = $event->basketId;
}

/**
 * @param InventoryItemToBaskedAdded $event
 */
protected function onInventoryItemToBaskedAdded(InventoryItemToBaskedAdded $event) {
    $this->inventoryItems[] = $event->inventoryItemId;
}
```

The trait will also throw an exception, if an event cannot be applied due to a missing apply method. You might want to be less strict in this regard, when dealing with CRUD only parts of your model, where you have no business logic attached to certain properties. This way you can get the distilled business logic in your model, without any noise.

### The Basket aggregate

```php
<?php
namespace Vendor\Foo\Domain\Model;

// ... NS imports

class Basket implements AggregateRootInterface {

    use AggregateSourcing;
    
    /**
     * @var array<string>
     */
    protected $inventoryItems = [];
    
    /**
     * @param string $basketId
     */
    public function __construct($basketId) {
        $this->applyNewEvent(new BasketCreated($basketId));
    }
    
    /**
     * @param string $inventoryItemId
     */
    public function addInventoryItem($inventoryItemId) {
        // business logic
        // validation logic
        
        $this->applyNewEvent(new InventoryItemToBaskedAdded($this->identifier, $inventoryItemId));
    }
    
    /**
     * @param BasketCreated $event
     */
    protected function onBasketCreated(BasketCreated $event) {
        $this->identifier = $event->basketId;
    }

    /**
     * @param InventoryItemToBaskedAdded $event
     */
    protected function onInventoryItemToBaskedAdded(InventoryItemToBaskedAdded $event) {
        $this->inventoryItems[] = $event->inventoryItemId;
    }

}

```

## Entities<a name="entities"></a>

Entities are very similar to aggregates (technically), however they are maintained and owned by an aggregate. The lifecycle of entities is the responsibility of the aggregate and you must only ever access entities through the aggregate.

Entities must implement the `Etg24\EventSourcing\EntityInterface`, the trait `Etg24\EventSourcing\EntitySourcing` provides behaviour.

When creating an entity, you must register the entity with the aggregate:

```php
public function onSomeThingsHappened(SomeThingsHappened $event) {
    $entity = new Entity(..);
    $this->entities[$entity->getIdentifier()] = $entity;
    $this->registerEntity($entity);
}

```

Note how the aggregate is handling the event that creates the entity, not the entity. This is why there are no checks inside the method `Entity::__construct`, as this is like a method that applies events.

```php
class Entity implements EntityInterface {

    use EntitySourcing;
    
    public function __construct($entityId) {
        $this->identifier = $entityId;
    }

}
```

This ensures that events are also forwarded to each entity. Entities must then expose which events they are subscribed to by implementing `canApplyEvent`.

```php
public function canApplyEvent(DomainEvent $event) {
    if ($event instanceof EntityEvent) {
        return ($event->entityId === $this->identifier);
    }
    
    return FALSE;
}
```

Applying events works exactly like replaying events in aggregate roots.

## Store<a name="event_store"></a>

At the moment, the only store backend implemented is [EventStore](https://geteventstore.com). You can implement the `StoreBackendInterface` and use the `Objects.yaml` to use your own implementations.

## Projections

Projections, also known as query model, are used to query data inside an event sourced environment. Basically, they are nothing but [event handlers](#event_handlers) that update some query optimized database.

todo: add some example for the MysqlProjector

## Queues<a name="queues"></a>

Message queues can be used to handle commands and events asynchronously or synchronously. At the moment, the only working queue is the `ImmediateQueue` that handles messages synchronously. My plan is to use TYPO3.Jobqueue in the future (no need to re-invent the wheel).

## Serialization

Currently, there are two serializers implemented:

* ArraySerializer: Converts messages into an array
* JsonSerializer: Converts messages into a json string

## Testing

todo: write about how event sourced models are tested (hint: it's not by using getters ;))

## Todo

* Finish this documentation
* Exception handling in every handler (command/event/projection)
* Implement snapshots
* Write tests for the package (yes, this is entirely untested, but it works :o!)

## License

Etg24.EventSourcing is released under the [MIT license](http://www.opensource.org/licenses/MIT).