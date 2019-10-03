/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.gridexport.abstract");
pimcore.gridexport.abstract = Class.create({
    name: t('export'),
    text: t('export'),
    warningText: t('object_export_warning'),
    downloadUrl: null,
    getExportSettingsContainer: function () {
        return null;
    }
});

pimcore.globalmanager.add("pimcore.gridexport", []);