Access Control Lists
====================

Security for domain objects (generally database entities) is implemented using
Access Control Lists (ACL). ACLs provide flexible permissions for individual
objects.

- View: View object
- Create: Create a new object
- Edit: Edit an existing object
- Delete: Delete an existing object
- Operator: View, Create, Edit, Delete permission
- Master: Operator permission, can manage all permissions up to operator level.
- Owner: Master permission, can grant master permission as well.
