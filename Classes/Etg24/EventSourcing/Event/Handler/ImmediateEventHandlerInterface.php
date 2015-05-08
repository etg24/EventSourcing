<?php
namespace Etg24\EventSourcing\Event\Handler;

/**
 * EventHandlers implementing this interface will receive all published events synchronously
 */
interface ImmediateEventHandlerInterface extends EventHandlerInterface {}