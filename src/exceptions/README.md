
# SPITFIRE EXCEPTIONS

Spitfire provides a very barebones set of basic exceptions to create a versatile
and extensible layer for packages to extend upon. It is not intended to be a thorough
and semantic description of the errors that can originate, but a good baseline for
inheriting products to work with.

There's two types of exceptions scopes:

  1. System: These exceptions help report system errors and issues that the user is neither
  responsible for, nor should be interested in.
  2. User: Report issues that are relevant to the user, like a missing permission or a quota
  being exceeded.

For each of these categories, Spitfire defines three types of permissions:

  1. Not Found Exceptions
  2. Permissions Exceptions
  3. Application exceptions

These indicate whether the system ran into an issue that is rooted within a resource being either 
unavailable or not existing, a resource being locked and the application or user not having
enough authority to unlock them, and everything else.

The idea behind these is to have each package inheriting from these define their own exceptions
that can be caught and managed, while making it comfortable for an application developer to generate
a set of just a few pages to report errors that the system may be running into.