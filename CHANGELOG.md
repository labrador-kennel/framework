# Changelog

## 1.0.0-beta3 - 2019-01-20

#### Added

- Adds a `HttpApplication::addMiddleware` method that allows `Middleware` to be added
to every request with the possibility of short-circuiting Router matching by returning 
a Response. This allows for functionality like CORS support without requiring going 
through the Routing layer and executing Controllers.

## 1.0.0-beta2 - 2019-01-19

#### Added

- Ensures that exceptions thrown by Controllers or Middleware are appropriately logged using the Logger implementation 
passed to the `HttpApplication` instance.

#### Changed

- Refactored the `AbstractHttpApplication` -> `HttpApplication` so that consumers of this framework are not required to 
extend their own class but can instead use a standardized implementation that expects dependencies passed to it as 
constructor dependencies.

## 1.0.0-beta - 2019-01-19

**This release represents a major BC Break as we incorporate Amphp's Event Loop support and move to an async 
architecture.** It should be assumed that most items below will represent a break in previous versions.

#### Added

- Adds an `AbstractHttpApplication` implementation that allows for the easy startup of an Amphp HTTP Server by simply providing the appropriate Logger, Router, and set of SocketServers to listen to.
- Adds a `Controller` interface that acts as an amphp http-server RequestHandler. At the moment this interface does little more than an amphp RequestHandler though we anticipate that changing in the future.
- Adds a `MiddlewareController` implementation that handles when amphp Middleware are added to a route.
- Adds a `FriendlyRouter` implementation that adds syntactic sugar onto lower-level Router implementations.
- Adds a `RouterPlugin` interface that will take advantage of Labrador 3's custom plugin functionality to allow adding routes to the Router at Engine bootup.

#### Changed

- Upgraded Labrador to use the most recent 3.0 branch to move to asynchronous processing with amphp.
- Changed the `Router` interface to reflect the changes moving to amphp http-server.
- Changed the `FastRouteRouter` implementation to no longer directly have convenience methods. Instead you should pass implementations of `Router` to `FriendlyRouter` for easier route additions.

#### Removed

- Removed the `StatusCodes` object as it was duplicating functionality provided by amphp's HTTP package.

## 0.6.0 - 2016-03-20

- **BC BREAK** Removes the Symfony\HttpFoundation library and transitions to a PSR-7 spec compliant library with 
  Zend\Diactoros. Please review commit message or PR #?? for more information.
- Updates Labrador to 2.0 which had a breaking change by removing the Plugin::boot method. Please see Labrador's 
  CHANGELOG for more information.

## 0.5.2 - 2016-02-15

- Fixes bug where an Auryn\\Injector was not being returned from `Services::wireObjectGraph` appropriately.

## v0.5.1 - 2016-02-14

- Add `Engine::onResponseSent` method for adding a listener to the `Engine::RESPONSE_SENT_EVENT`.
- Adds `public` visibility keyword to appropriate functions, makes use of `Engine` class name unambiguous.

## v0.5.0 - 2016-02-14

- **BC BREAK** Renames `Services::createInjector` to `Services::wireObjectGraph` and allows an Injector to be passed to 
  add services instead of simply creating a new Injector.
- Adds a new event `Engine::RESPONSE_SENT_EVENT` that will be triggered *everytime* a Response is sent to the user. Before
  this change there was no guaranteed mechanism for capturing the Response sent to the user. While the
  `Engine::AFTER_CONTROLLER_EVENT` does provide the Response it is not guaranteed to be emitted on every Request (e.g. 
  an Exception is thrown or a Response is set in a `Engine::BEFORE_CONTROLLER_EVENT` listener).

## v0.4.0 - 2016-02-13

- Updates FastRoute to 0.7.0
- Updates Labrador to 1.1.0
- Refactors core Labrador events to not use HttpEvents and to use those provided by labrador proper. A result of this 
  is the removal of the HttpEventFactory
- Refactors HandlerResolver to accept a Request as the first argument for more flexible and powerful handler resolving.
- Refactors the InjectorExecutorResolver to not require the Request be shared in the container. A side effect of this is 
  that all controller parameters that want a Request MUST be named either `$request` or `$req`.
- Refactors how we invoke a controller object's beforeController and afterController methods. Several improvements were 
  made, please see the specific commit message for more information.
- Ensures that custom FastRoute parameters are appropriately URL decoded.

## v0.2.0 - 2016-01-08

- Removes Telluris dependency
- Updates Labrador to 0.3.1 and Symfony HTTP Foundation to 3.0.1
- Moves autoloading from PSR-0 to PSR-4

## v0.1.0 - 2015-08-23

- Initial launch
- Add Engine implementation for handling an HTTP request.
- Interface to handle request matching `Cspray\Labrador\Http\Router\Router` with a 
  default implementation proxying all functionality to FastRoute.
- Interface to convert a handler into a callable, `Cspray\Labrador\Http\HandlerResolver\HandlerResolver`. 
  Includes the following implementations:
    - CallableResolver that will match a handler that is a callable.
    - ControllerActionResolver will attempt to match a handler that has a `#` delimiter by creating an 
      object from the string on the left side of the `#` while using the right side as the name of 
      method to invoke on the object.
    - ResponseResolver will match any object that is a Response.
    - ResolverChain will invoke a series of HandlerResolver implementations until a match is found.
    - InjectorExecutableResolver is a decorator that will take any resolved callable and invoke it 
      with Auryn\Injector::execute to automatically provision any dependencies needed.
- Replaces the Event types from Labrador to provide a Request instance for every triggered event.
- Adds 2 events: `labrador.http.before_controller` and `labrador.http.after_controller`
- Adds a Controller object and Plugin to share them as a service in the Injector and to invoke
  the beforeController() and afterController() methods during the appropriate event.
