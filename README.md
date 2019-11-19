Overwrite module 

The Overwrite module presents a user with an additional tab when presented with an entity.  This new tab allows a user to overwrite the field data that is associated with the entity.  This is useful in the case of entities that are created and updated from an external feed or api.  Sometime these entities need adjustments made such as a different title or image.  With the Overwrite tab, the user can select the field that they wish to adjust, make changes, and save.  The changes to the entity are listed in the "overwrite" tab.  When you click the "view" tab, the user will see the entity with their adjustments made from the overwrite tab.  When the user clicks the "edit" tab they will see the original entity without the changes made from the "overwrite" tab.  This is because the entity is untouched by the overwrite module.  Instead of writing changes to the entity the overwrite module creates Overwrite entities.  The Overwrite entities contain a reference to the entity, the method for applying the update (append, prepend, replace), and finally a blob of serialized field data.  Now when the entity gets updated, the changes made in the "overwrite" tab will persist.

Installation

Install both the overwrite and overwrite_activate modules.
The overwrite module allows for the creation of Overwrite entities.
The overwrite_activate module implements hook_entity_load, allowing Overwrite entities to be viewable.
The seperate modules were used so that the effects of the overwrite module can be disabled without deleting any of the Overwrite entities.
