# jsondb
A php implementation of a simple json database management system with advanced select methods and sorting.


## Async
The save method in the BaseEntity saves to a asynchronous queue. The queue server worker can be started with composer start-server.
The save method also accepts a parameter to decide whether to work with a queue or not. 
save(false) immediately executes the action without a queue and without a queue worker. 
The save method returns a Promise object.

By calling save()->wait() the program waits with further execution until the queue job is finished.
The wait() function immediately continues the program if the save(false) method was called with deactivated queue.
