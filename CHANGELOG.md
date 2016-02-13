# Changelog

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
