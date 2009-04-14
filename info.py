# encoding: utf-8

# =============================================================================
# package info
# =============================================================================
NAME = 'symmetrics_module_invoicepdf'

TAGS = ('magento', 'module', 'symmetrics')

LICENSE = 'AFL 3.0'

HOMEPAGE = 'http://www.symmetrics.de'

INSTALL_PATH = ''

# =============================================================================
# responsibilities
# =============================================================================
TEAM_LEADER = {
    'Sergej Braznikov': 'sb@symmetrics.de'
}

MAINTAINER = {
    'Eugen Gitin': 'eg@symmetrics.de'
}

AUTHORS = {
    'Eugen Gitin': 'eg@symmetrics.de'
}

# =============================================================================
# additional infos
# =============================================================================
INFO = 'symmetrics Rechnungsvorlage'

SUMMARY = '''
    Rechtssichere (Deutschland) Vorlage für die Rechnungen
'''

NOTES = '''
'''

# =============================================================================
# relations
# =============================================================================
REQUIRES = {
    'magento': '*',
    'symmetrics_config_german': '*',
    'symmetrics_module_impressum': '*',
}

EXCLUDES = {
}

DEPENDS_ON_FILES = (
    'app/code/core/Mage/Core/Helper/Abstract.php',
    'app/code/core/Mage/Sales/Model/Order/Pdf/Items/Invoice/Default.php',
    'app/code/core/Mage/Sales/Model/Order/Pdf/Abstract.php',
)

PEAR_KEY = ''

COMPATIBLE_WITH = {
    'magento': '1.3.0'
}
