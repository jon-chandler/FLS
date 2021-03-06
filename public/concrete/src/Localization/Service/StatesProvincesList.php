<?php
namespace Concrete\Core\Localization\Service;

use Events;

class StatesProvincesList
{
    protected $localizedStatesProvinces = [];

    /**
     * Returns the list of States/Provinces for some countries (States/Provinces are sorted alphabetically).
     *
     * @return array Returns an array whose keys are the country codes and the values are arrays (with keys: State/Province code, values: State/Province names)
     */
    public function getAll()
    {
        $locale = \Localization::activeLocale();
        if (!isset($this->localizedStatesProvinces[$locale])) {
            $provinces = [
                'US' => [
                    'AK' => tc('US State', 'Alaska'),
                    'AL' => tc('US State', 'Alabama'),
                    'AR' => tc('US State', 'Arkansas'),
                    'AZ' => tc('US State', 'Arizona'),
                    'CA' => tc('US State', 'California'),
                    'CO' => tc('US State', 'Colorado'),
                    'CT' => tc('US State', 'Connecticut'),
                    'DC' => tc('US State', 'Washington, DC'),
                    'DE' => tc('US State', 'Delaware'),
                    'FL' => tc('US State', 'Florida'),
                    'GA' => tc('US State', 'Georgia'),
                    'HI' => tc('US State', 'Hawaii'),
                    'IA' => tc('US State', 'Iowa'),
                    'ID' => tc('US State', 'Idaho'),
                    'IL' => tc('US State', 'Illinois'),
                    'IN' => tc('US State', 'Indiana'),
                    'KS' => tc('US State', 'Kansas'),
                    'KY' => tc('US State', 'Kentucky'),
                    'LA' => tc('US State', 'Louisiana'),
                    'MA' => tc('US State', 'Massachusetts'),
                    'MD' => tc('US State', 'Maryland'),
                    'ME' => tc('US State', 'Maine'),
                    'MI' => tc('US State', 'Michigan'),
                    'MN' => tc('US State', 'Minnesota'),
                    'MO' => tc('US State', 'Missouri'),
                    'MS' => tc('US State', 'Mississippi'),
                    'MT' => tc('US State', 'Montana'),
                    'NC' => tc('US State', 'North Carolina'),
                    'ND' => tc('US State', 'North Dakota'),
                    'NE' => tc('US State', 'Nebraska'),
                    'NH' => tc('US State', 'New Hampshire'),
                    'NJ' => tc('US State', 'New Jersey'),
                    'NM' => tc('US State', 'New Mexico'),
                    'NV' => tc('US State', 'Nevada'),
                    'NY' => tc('US State', 'New York'),
                    'OH' => tc('US State', 'Ohio'),
                    'OK' => tc('US State', 'Oklahoma'),
                    'OR' => tc('US State', 'Oregon'),
                    'PA' => tc('US State', 'Pennsylvania'),
                    'RI' => tc('US State', 'Rhode Island'),
                    'SC' => tc('US State', 'South Carolina'),
                    'SD' => tc('US State', 'South Dakota'),
                    'TN' => tc('US State', 'Tennessee'),
                    'TX' => tc('US State', 'Texas'),
                    'UT' => tc('US State', 'Utah'),
                    'VA' => tc('US State', 'Virginia'),
                    'VT' => tc('US State', 'Vermont'),
                    'WA' => tc('US State', 'Washington'),
                    'WI' => tc('US State', 'Wisconsin'),
                    'WV' => tc('US State', 'West Virginia'),
                    'WY' => tc('US State', 'Wyoming'),
                ],

                'CA' => [
                    'AB' => tc('Canadian Province', 'Alberta'),
                    'BC' => tc('Canadian Province', 'British Columbia'),
                    'MB' => tc('Canadian Province', 'Manitoba'),
                    'NB' => tc('Canadian Province', 'New Brunswick'),
                    'NF' => tc('Canadian Province', 'Newfoundland'),
                    'NT' => tc('Canadian Province', 'Northwest Territories'),
                    'NS' => tc('Canadian Province', 'Nova Scotia'),
                    'NU' => tc('Canadian Province', 'Nunavut'),
                    'ON' => tc('Canadian Province', 'Ontario'),
                    'PE' => tc('Canadian Province', 'Prince Edward Island'),
                    'QC' => tc('Canadian Province', 'Quebec'),
                    'SK' => tc('Canadian Province', 'Saskatchewan'),
                    'YT' => tc('Canadian Province', 'Yukon'),
                ],

                'AU' => [
                    'ACT' => tc('Australian State', 'Australian Capital Territory'),
                    'NSW' => tc('Australian State', 'New South Wales'),
                    'NT' => tc('Australian State', 'Northern Territory'),
                    'QLD' => tc('Australian State', 'Queensland'),
                    'SA' => tc('Australian State', 'South Australia'),
                    'TAS' => tc('Australian State', 'Tasmania'),
                    'VIC' => tc('Australian State', 'Victoria'),
                    'WA' => tc('Australian State', 'Western Australia'),
                ],

                'DE' => [
                    'BB' => tc('German State', 'Brandenburg'),
                    'BE' => tc('German State', 'Berlin'),
                    'BW' => tc('German State', 'Baden-W??rttemberg'),
                    'BY' => tc('German State', 'Bavaria'),
                    'HB' => tc('German State', 'Bremen'),
                    'HE' => tc('German State', 'Hesse'),
                    'HH' => tc('German State', 'Hamburg'),
                    'MV' => tc('German State', 'Mecklenburg-Vorpommern'),
                    'NI' => tc('German State', 'Lower Saxony'),
                    'NW' => tc('German State', 'North Rhine-Westphalia'),
                    'RP' => tc('German State', 'Rhineland-Palatinate'),
                    'SH' => tc('German State', 'Schleswig-Holstein'),
                    'SL' => tc('German State', 'Saarland'),
                    'SN' => tc('German State', 'Saxony'),
                    'ST' => tc('German State', 'Saxony-Anhalt'),
                    'TH' => tc('German State', 'Thuringia'),
                ],

                'FR' => [
                    '01' => tc('French Department', 'Ain'),
                    '02' => tc('French Department', 'Aisne'),
                    '03' => tc('French Department', 'Allier'),
                    '04' => tc('French Department', 'Alpes-de-Haute-Provence'),
                    '05' => tc('French Department', 'Hautes-Alpes'),
                    '06' => tc('French Department', 'Alpes-Maritimes'),
                    '07' => tc('French Department', 'Ard??che'),
                    '08' => tc('French Department', 'Ardennes'),
                    '09' => tc('French Department', 'Ari??ge'),
                    '10' => tc('French Department', 'Aube'),
                    '11' => tc('French Department', 'Aude'),
                    '12' => tc('French Department', 'Aveyron'),
                    '13' => tc('French Department', 'Bouches-du-Rh??ne'),
                    '14' => tc('French Department', 'Calvados'),
                    '15' => tc('French Department', 'Cantal'),
                    '16' => tc('French Department', 'Charente'),
                    '17' => tc('French Department', 'Charente-Maritime'),
                    '18' => tc('French Department', 'Cher'),
                    '19' => tc('French Department', 'Corr??ze'),
                    '2A' => tc('French Department', 'Corse-du-Sud'),
                    '2B' => tc('French Department', 'Haute-Corse'),
                    '21' => tc('French Department', 'C??te-d\'Or'),
                    '22' => tc('French Department', 'C??tes-d\'Armor'),
                    '23' => tc('French Department', 'Creuse'),
                    '24' => tc('French Department', 'Dordogne'),
                    '25' => tc('French Department', 'Doubs'),
                    '26' => tc('French Department', 'Dr??me'),
                    '27' => tc('French Department', 'Eure'),
                    '28' => tc('French Department', 'Eure-et-Loir'),
                    '29' => tc('French Department', 'Finist??re'),
                    '30' => tc('French Department', 'Gard'),
                    '31' => tc('French Department', 'Haute-Garonne'),
                    '32' => tc('French Department', 'Gers'),
                    '33' => tc('French Department', 'Gironde'),
                    '34' => tc('French Department', 'H??rault'),
                    '35' => tc('French Department', 'Ille-et-Vilaine'),
                    '36' => tc('French Department', 'Indre'),
                    '37' => tc('French Department', 'Indre-et-Loire'),
                    '38' => tc('French Department', 'Is??re'),
                    '39' => tc('French Department', 'Jura'),
                    '40' => tc('French Department', 'Landes'),
                    '41' => tc('French Department', 'Loir-et-Cher'),
                    '42' => tc('French Department', 'Loire'),
                    '43' => tc('French Department', 'Haute-Loire'),
                    '44' => tc('French Department', 'Loire-Atlantique'),
                    '45' => tc('French Department', 'Loiret'),
                    '46' => tc('French Department', 'Lot'),
                    '47' => tc('French Department', 'Lot-et-Garonne'),
                    '48' => tc('French Department', 'Loz??re'),
                    '49' => tc('French Department', 'Maine-et-Loire'),
                    '50' => tc('French Department', 'Manche'),
                    '51' => tc('French Department', 'Marne'),
                    '52' => tc('French Department', 'Haute-Marne'),
                    '53' => tc('French Department', 'Mayenne'),
                    '54' => tc('French Department', 'Meurthe-et-Moselle'),
                    '55' => tc('French Department', 'Meuse'),
                    '56' => tc('French Department', 'Morbihan'),
                    '57' => tc('French Department', 'Moselle'),
                    '58' => tc('French Department', 'Ni??vre'),
                    '59' => tc('French Department', 'Nord'),
                    '60' => tc('French Department', 'Oise'),
                    '61' => tc('French Department', 'Orne'),
                    '62' => tc('French Department', 'Pas-de-Calais'),
                    '63' => tc('French Department', 'Puy-de-D??me'),
                    '64' => tc('French Department', 'Pyr??n??es-Atlantiques'),
                    '65' => tc('French Department', 'Hautes-Pyr??n??es'),
                    '66' => tc('French Department', 'Pyr??n??es-Orientales'),
                    '67' => tc('French Department', 'Bas-Rhin'),
                    '68' => tc('French Department', 'Haut-Rhin'),
                    '69' => tc('French Department', 'Rh??ne'),
                    '69M' => tc('French Department', 'Metropolis of Lyon'),
                    '70' => tc('French Department', 'Haute-Sa??ne'),
                    '71' => tc('French Department', 'Sa??ne-et-Loire'),
                    '72' => tc('French Department', 'Sarthe'),
                    '73' => tc('French Department', 'Savoie'),
                    '74' => tc('French Department', 'Haute-Savoie'),
                    '75' => tc('French Department', 'Paris'),
                    '76' => tc('French Department', 'Seine-Maritime'),
                    '77' => tc('French Department', 'Seine-et-Marne'),
                    '78' => tc('French Department', 'Yvelines'),
                    '79' => tc('French Department', 'Deux-S??vres'),
                    '80' => tc('French Department', 'Somme'),
                    '81' => tc('French Department', 'Tarn'),
                    '82' => tc('French Department', 'Tarn-et-Garonne'),
                    '83' => tc('French Department', 'Var'),
                    '84' => tc('French Department', 'Vaucluse'),
                    '85' => tc('French Department', 'Vend??e'),
                    '86' => tc('French Department', 'Vienne'),
                    '87' => tc('French Department', 'Haute-Vienne'),
                    '88' => tc('French Department', 'Vosges'),
                    '89' => tc('French Department', 'Yonne'),
                    '90' => tc('French Department', 'Territoire de Belfort'),
                    '91' => tc('French Department', 'Essonne'),
                    '92' => tc('French Department', 'Hauts-de-Seine'),
                    '93' => tc('French Department', 'Seine-Saint-Denis'),
                    '94' => tc('French Department', 'Val-de-Marne'),
                    '95' => tc('French Department', 'Val-d\'Oise'),
                    '971' => tc('French Department', 'Guadeloupe'),
                    '972' => tc('French Department', 'Martinique'),
                    '973' => tc('French Department', 'Guyane'),
                    '974' => tc('French Department', 'La R??union'),
                    '976' => tc('French Department', 'Mayotte'),
                ],

                'GB' => [
                    'ANGLES' => tc('British Region', 'Anglesey'),
                    'ANGUS' => tc('British Region', 'Angus'),
                    'ARBERD' => tc('British Region', 'Aberdeenshire'),
                    'ARGYLL' => tc('British Region', 'Argyllshire'),
                    'AYRSH' => tc('British Region', 'Ayrshire'),
                    'BANFF' => tc('British Region', 'Banffshire'),
                    'BEDS' => tc('British Region', 'Bedfordshire'),
                    'BERKS' => tc('British Region', 'Berkshire'),
                    'BERWICK' => tc('British Region', 'Berwickshire'),
                    'BRECK' => tc('British Region', 'Brecknockshire'),
                    'BUCKS' => tc('British Region', 'Buckinghamshire'),
                    'BUTE' => tc('British Region', 'Buteshire'),
                    'CAERN' => tc('British Region', 'Caernarfonshire'),
                    'CAITH' => tc('British Region', 'Caithness'),
                    'CAMBS' => tc('British Region', 'Cambridgeshire'),
                    'CARDIG' => tc('British Region', 'Cardiganshire'),
                    'CARMA' => tc('British Region', 'Carmathenshire'),
                    'CHESH' => tc('British Region', 'Cheshire'),
                    'CLACKM' => tc('British Region', 'Clackmannanshire'),
                    'CORN' => tc('British Region', 'Cornwall'),
                    'CROMART' => tc('British Region', 'Cromartyshire'),
                    'CUMB' => tc('British Region', 'Cumberland'),
                    'DENBIG' => tc('British Region', 'Denbighshire'),
                    'DERBY' => tc('British Region', 'Derbyshire'),
                    'DEVON' => tc('British Region', 'Devon'),
                    'DORSET' => tc('British Region', 'Dorset'),
                    'DUMFR' => tc('British Region', 'Dumfriesshire'),
                    'DUNBART' => tc('British Region', 'Dunbartonshire'),
                    'DURHAM' => tc('British Region', 'Durham'),
                    'EASTL' => tc('British Region', 'East Lothian'),
                    'ESSEX' => tc('British Region', 'Essex'),
                    'FIFE' => tc('British Region', 'Fife'),
                    'FLINTS' => tc('British Region', 'Flintshire'),
                    'GLAMO' => tc('British Region', 'Glamorgan'),
                    'GLOUS' => tc('British Region', 'Gloucestershire'),
                    'HANTS' => tc('British Region', 'Hampshire'),
                    'HEREF' => tc('British Region', 'Herefordshire'),
                    'HERTS' => tc('British Region', 'Hertfordshire'),
                    'HUNTS' => tc('British Region', 'Huntingdonshire'),
                    'INVERN' => tc('British Region', 'Inverness-shire'),
                    'KENT' => tc('British Region', 'Kent'),
                    'KINCARD' => tc('British Region', 'Kincardineshire'),
                    'KINROSS' => tc('British Region', 'Kinross-shire'),
                    'KIRKCUD' => tc('British Region', 'Kircudbrightshire'),
                    'LANARK' => tc('British Region', 'Lanarkshire'),
                    'LANCS' => tc('British Region', 'Lancashire'),
                    'LEICS' => tc('British Region', 'Leicestershire'),
                    'LINCS' => tc('British Region', 'Lincolnshire'),
                    'LONDON' => tc('British Region', 'London'),
                    'MERION' => tc('British Region', 'Merioneth'),
                    'MERSEYSIDE' => tc('British Region', 'Merseyside'),
                    'MIDDLE' => tc('British Region', 'Middlesex'),
                    'MIDLOTH' => tc('British Region', 'Midlothian'),
                    'MONMOUTH' => tc('British Region', 'Monmouthshire'),
                    'MONTG' => tc('British Region', 'Mongtomeryshire'),
                    'MORAY' => tc('British Region', 'Morayshire'),
                    'NAIRN' => tc('British Region', 'Nairnshire'),
                    'NHANTS' => tc('British Region', 'Northamptonshire'),
                    'NORF' => tc('British Region', 'Norfolk'),
                    'NOTTS' => tc('British Region', 'Nottinghamshire'),
                    'NTHUMB' => tc('British Region', 'Northumberland'),
                    'ORKNEY' => tc('British Region', 'Orkeny'),
                    'OXON' => tc('British Region', 'Oxfordshire'),
                    'PEEBLESS' => tc('British Region', 'Peeblesshire'),
                    'PEMBR' => tc('British Region', 'Pembrokeshire'),
                    'PERTH' => tc('British Region', 'Perthshire'),
                    'RADNOR' => tc('British Region', 'Radnorshire'),
                    'RENFREW' => tc('British Region', 'Renfrewshire'),
                    'ROSSSH' => tc('British Region', 'Ross-shire'),
                    'ROXBURGH' => tc('British Region', 'Roxburghshire'),
                    'RUTL' => tc('British Region', 'Rutland'),
                    'SELKIRK' => tc('British Region', 'Selkirkshire'),
                    'SHETLAND' => tc('British Region', 'Shetland'),
                    'SHROPS' => tc('British Region', 'Shropshire'),
                    'SOM' => tc('British Region', 'Somerset'),
                    'STAFFS' => tc('British Region', 'Staffordshire'),
                    'STIRLING' => tc('British Region', 'Stirlingshire'),
                    'SUFF' => tc('British Region', 'Suffolk'),
                    'SURREY' => tc('British Region', 'Surrey'),
                    'SUSS' => tc('British Region', 'Sussex'),
                    'SUTHER' => tc('British Region', 'Sutherland'),
                    'WARKS' => tc('British Region', 'Warwickshire'),
                    'WESTL' => tc('British Region', 'West Lothian'),
                    'WESTMOR' => tc('British Region', 'Westmorland'),
                    'WIGTOWN' => tc('British Region', 'Wigtownshire'),
                    'WILTS' => tc('British Region', 'Wiltshire'),
                    'WORCES' => tc('British Region', 'Worcestershire'),
                    'YORK' => tc('British Region', 'Yorkshire'),
                ],

                'IE' => [
                    'CO ANTRIM' => tc('Irish County', 'County Antrim'),
                    'CO ARMAGH' => tc('Irish County', 'County Armagh'),
                    'CO CARLOW' => tc('Irish County', 'County Carlow'),
                    'CO CAVAN' => tc('Irish County', 'County Cavan'),
                    'CO CLARE' => tc('Irish County', 'County Clare'),
                    'CO CORK' => tc('Irish County', 'County Cork'),
                    'CO DERRY' => tc('Irish County', 'County Londonderry'),
                    'CO DONEGAL' => tc('Irish County', 'County Donegal'),
                    'CO DOWN' => tc('Irish County', 'County Down'),
                    'CO DUBLIN' => tc('Irish County', 'County Dublin'),
                    'CO FERMANAGH' => tc('Irish County', 'County Fermanagh'),
                    'CO GALWAY' => tc('Irish County', 'County Galway'),
                    'CO KERRY' => tc('Irish County', 'County Kerry'),
                    'CO KILDARE' => tc('Irish County', 'County Kildare'),
                    'CO KILKENNY' => tc('Irish County', 'County Kilkenny'),
                    'CO LAOIS' => tc('Irish County', 'County Laois'),
                    'CO LEITRIM' => tc('Irish County', 'County Leitrim'),
                    'CO LIMERICK' => tc('Irish County', 'County Limerick'),
                    'CO LONGFORD' => tc('Irish County', 'County Longford'),
                    'CO LOUTH' => tc('Irish County', 'County Louth'),
                    'CO MAYO' => tc('Irish County', 'County Mayo'),
                    'CO MEATH' => tc('Irish County', 'County Meath'),
                    'CO MONAGHAN' => tc('Irish County', 'County Monaghan'),
                    'CO OFFALY' => tc('Irish County', 'County Offaly'),
                    'CO ROSCOMMON' => tc('Irish County', 'County Roscommon'),
                    'CO SLIGO' => tc('Irish County', 'County Sligo'),
                    'CO TIPPERARY' => tc('Irish County', 'County Tipperary'),
                    'CO TYRONE' => tc('Irish County', 'County Tyrone'),
                    'CO WATERFORD' => tc('Irish County', 'County Waterford'),
                    'CO WESTMEATH' => tc('Irish County', 'County Westmeath'),
                    'CO WEXFORD' => tc('Irish County', 'County Wexford'),
                    'CO WICKLOW' => tc('Irish County', 'County Wicklow'),
                ],

                'NL' => [
                    'DR' => tc('Dutch Province', 'Drente'),
                    'FL' => tc('Dutch Province', 'Flevoland'),
                    'FR' => tc('Dutch Province', 'Frysl??n'),
                    'GL' => tc('Dutch Province', 'Gelderland'),
                    'GR' => tc('Dutch Province', 'Groningen'),
                    'LB' => tc('Dutch Province', 'Limburg'),
                    'NB' => tc('Dutch Province', 'North Brabant'),
                    'NH' => tc('Dutch Province', 'North Holland'),
                    'OV' => tc('Dutch Province', 'Overijssel'),
                    'UT' => tc('Dutch Province', 'Utrecht'),
                    'ZH' => tc('Dutch Province', 'South Holland'),
                    'ZL' => tc('Dutch Province', 'Zeeland'),
                ],

                'BR' => [
                    'AC' => tc('Brazilian State', 'Acre'),
                    'AL' => tc('Brazilian State', 'Alagoas'),
                    'AM' => tc('Brazilian State', 'Amazonas'),
                    'AP' => tc('Brazilian State', 'Amap??'),
                    'BA' => tc('Brazilian State', 'Bahia'),
                    'CE' => tc('Brazilian State', 'Cear??'),
                    'DF' => tc('Brazilian State', 'Distrito Federal'),
                    'ES' => tc('Brazilian State', 'Espirito Santo'),
                    'FN' => tc('Brazilian State', 'Fernando de Noronha'),
                    'GO' => tc('Brazilian State', 'Goi??s'),
                    'MA' => tc('Brazilian State', 'Maranh??o'),
                    'MG' => tc('Brazilian State', 'Minas Gerais'),
                    'MS' => tc('Brazilian State', 'Mato Grosso do Sul'),
                    'MT' => tc('Brazilian State', 'Mato Grosso'),
                    'PA' => tc('Brazilian State', 'Par??'),
                    'PB' => tc('Brazilian State', 'Para??ba'),
                    'PE' => tc('Brazilian State', 'Pernambuco'),
                    'PI' => tc('Brazilian State', 'Piau??'),
                    'PR' => tc('Brazilian State', 'Paran??'),
                    'RJ' => tc('Brazilian State', 'Rio de Janeiro'),
                    'RN' => tc('Brazilian State', 'Rio Grande do Norte'),
                    'RO' => tc('Brazilian State', 'Rond??nia'),
                    'RR' => tc('Brazilian State', 'Roraima'),
                    'RS' => tc('Brazilian State', 'Rio Grande do Sul'),
                    'SC' => tc('Brazilian State', 'Santa Catarina'),
                    'SE' => tc('Brazilian State', 'Sergipe'),
                    'SP' => tc('Brazilian State', 'S??o Paulo'),
                    'TO' => tc('Brazilian State', 'Tocantins'),
                ],

                'IT' => [
                    'AG' => tc('Italian Provinces', 'Agrigento'),
                    'AL' => tc('Italian Provinces', 'Alessandria'),
                    'AN' => tc('Italian Provinces', 'Ancona'),
                    'AO' => tc('Italian Provinces', 'Aosta'),
                    'AP' => tc('Italian Provinces', 'Ascoli Piceno'),
                    'AQ' => tc('Italian Provinces', 'L\'Aquila'),
                    'AR' => tc('Italian Provinces', 'Arezzo'),
                    'AT' => tc('Italian Provinces', 'Asti'),
                    'AV' => tc('Italian Provinces', 'Avellino'),
                    'BA' => tc('Italian Provinces', 'Bari'),
                    'BG' => tc('Italian Provinces', 'Bergamo'),
                    'BI' => tc('Italian Provinces', 'Biella'),
                    'BL' => tc('Italian Provinces', 'Belluno'),
                    'BN' => tc('Italian Provinces', 'Benevento'),
                    'BO' => tc('Italian Provinces', 'Bologna'),
                    'BR' => tc('Italian Provinces', 'Brindisi'),
                    'BS' => tc('Italian Provinces', 'Brescia'),
                    'BT' => tc('Italian Provinces', 'Barletta-Andria-Trani'),
                    'BZ' => tc('Italian Provinces', 'South Tyrol'),
                    'CA' => tc('Italian Provinces', 'Cagliari'), // Replaced by the Province of South Sardinia on 2016-02-04
                    'CB' => tc('Italian Provinces', 'Campobasso'),
                    'CE' => tc('Italian Provinces', 'Caserta'),
                    'CH' => tc('Italian Provinces', 'Chieti'),
                    'CI' => tc('Italian Provinces', 'Carbonia-Iglesias'), // Replaced by the Province of South Sardinia on 2016-02-04
                    'CL' => tc('Italian Provinces', 'Caltanissetta'),
                    'CN' => tc('Italian Provinces', 'Cuneo'),
                    'CO' => tc('Italian Provinces', 'Como'),
                    'CR' => tc('Italian Provinces', 'Cremona'),
                    'CS' => tc('Italian Provinces', 'Cosenza'),
                    'CT' => tc('Italian Provinces', 'Catania'),
                    'CZ' => tc('Italian Provinces', 'Catanzaro'),
                    'EN' => tc('Italian Provinces', 'Enna'),
                    'FC' => tc('Italian Provinces', 'Forl??-Cesena'),
                    'FE' => tc('Italian Provinces', 'Ferrara'),
                    'FG' => tc('Italian Provinces', 'Foggia'),
                    'FI' => tc('Italian Provinces', 'Florence'),
                    'FM' => tc('Italian Provinces', 'Fermo'),
                    'FR' => tc('Italian Provinces', 'Frosinone'),
                    'GE' => tc('Italian Provinces', 'Genoa'),
                    'GO' => tc('Italian Provinces', 'Gorizia'),
                    'GR' => tc('Italian Provinces', 'Grosseto'),
                    'IM' => tc('Italian Provinces', 'Imperia'),
                    'IS' => tc('Italian Provinces', 'Isernia'),
                    'KR' => tc('Italian Provinces', 'Crotone'),
                    'LC' => tc('Italian Provinces', 'Lecco'),
                    'LE' => tc('Italian Provinces', 'Lecce'),
                    'LI' => tc('Italian Provinces', 'Livorno'),
                    'LO' => tc('Italian Provinces', 'Lodi'),
                    'LT' => tc('Italian Provinces', 'Latina'),
                    'LU' => tc('Italian Provinces', 'Lucca'),
                    'MB' => tc('Italian Provinces', 'Monza and Brianza'),
                    'MC' => tc('Italian Provinces', 'Macerata'),
                    'ME' => tc('Italian Provinces', 'Messina'),
                    'MI' => tc('Italian Provinces', 'Milan'),
                    'MN' => tc('Italian Provinces', 'Mantua'),
                    'MO' => tc('Italian Provinces', 'Modena'),
                    'MS' => tc('Italian Provinces', 'Massa and Carrara'),
                    'MT' => tc('Italian Provinces', 'Matera'),
                    'NA' => tc('Italian Provinces', 'Naples'),
                    'NO' => tc('Italian Provinces', 'Novara'),
                    'NU' => tc('Italian Provinces', 'Nuoro'),
                    'OG' => tc('Italian Provinces', 'Ogliastra'), // Merged into the Province of Nuoro on 2016-02-04
                    'OR' => tc('Italian Provinces', 'Oristano'),
                    'OT' => tc('Italian Provinces', 'Olbia-Tempio'), // Merged into the Province of Sassari on 2016-02-04
                    'PA' => tc('Italian Provinces', 'Palermo'),
                    'PC' => tc('Italian Provinces', 'Piacenza'),
                    'PD' => tc('Italian Provinces', 'Padua'),
                    'PE' => tc('Italian Provinces', 'Pescara'),
                    'PG' => tc('Italian Provinces', 'Perugia'),
                    'PI' => tc('Italian Provinces', 'Pisa'),
                    'PN' => tc('Italian Provinces', 'Pordenone'),
                    'PO' => tc('Italian Provinces', 'Prato'),
                    'PR' => tc('Italian Provinces', 'Parma'),
                    'PT' => tc('Italian Provinces', 'Pistoia'),
                    'PU' => tc('Italian Provinces', 'Pesaro and Urbino'),
                    'PV' => tc('Italian Provinces', 'Pavia'),
                    'PZ' => tc('Italian Provinces', 'Potenza'),
                    'RA' => tc('Italian Provinces', 'Ravenna'),
                    'RC' => tc('Italian Provinces', 'Reggio Calabria'),
                    'RE' => tc('Italian Provinces', 'Reggio Emilia'),
                    'RG' => tc('Italian Provinces', 'Ragusa'),
                    'RI' => tc('Italian Provinces', 'Rieti'),
                    'RM' => tc('Italian Provinces', 'Rome'),
                    'RN' => tc('Italian Provinces', 'Rimini'),
                    'RO' => tc('Italian Provinces', 'Rovigo'),
                    'SA' => tc('Italian Provinces', 'Salerno'),
                    'SI' => tc('Italian Provinces', 'Siena'),
                    'SO' => tc('Italian Provinces', 'Sondrio'),
                    'SP' => tc('Italian Provinces', 'La Spezia'),
                    'SR' => tc('Italian Provinces', 'Syracuse'),
                    'SS' => tc('Italian Provinces', 'Sassari'),
                    'SU' => tc('Italian Provinces', 'South Sardinia'), // Since 2016-02-04
                    'SV' => tc('Italian Provinces', 'Savona'),
                    'TA' => tc('Italian Provinces', 'Taranto'),
                    'TE' => tc('Italian Provinces', 'Teramo'),
                    'TN' => tc('Italian Provinces', 'Trento'),
                    'TO' => tc('Italian Provinces', 'Turin'),
                    'TP' => tc('Italian Provinces', 'Trapani'),
                    'TR' => tc('Italian Provinces', 'Terni'),
                    'TS' => tc('Italian Provinces', 'Trieste'),
                    'TV' => tc('Italian Provinces', 'Treviso'),
                    'UD' => tc('Italian Provinces', 'Udine'),
                    'VA' => tc('Italian Provinces', 'Varese'),
                    'VB' => tc('Italian Provinces', 'Verbano-Cusio-Ossola'),
                    'VC' => tc('Italian Provinces', 'Vercelli'),
                    'VE' => tc('Italian Provinces', 'Venice'),
                    'VI' => tc('Italian Provinces', 'Vicenza'),
                    'VR' => tc('Italian Provinces', 'Verona'),
                    'VS' => tc('Italian Provinces', 'Medio Campidano'), // Replaced by the Province of South Sardinia on 2016-02-04
                    'VT' => tc('Italian Provinces', 'Viterbo'),
                    'VV' => tc('Italian Provinces', 'Vibo Valentia'),
                ],

                'JP' => [
                    '01' => tc('Japanese Prefecture', 'Hokkaido'),
                    '02' => tc('Japanese Prefecture', 'Aomori'),
                    '03' => tc('Japanese Prefecture', 'Iwate'),
                    '04' => tc('Japanese Prefecture', 'Miyagi'),
                    '05' => tc('Japanese Prefecture', 'Akita'),
                    '06' => tc('Japanese Prefecture', 'Yamagata'),
                    '07' => tc('Japanese Prefecture', 'Fukushima'),
                    '08' => tc('Japanese Prefecture', 'Ibaraki'),
                    '09' => tc('Japanese Prefecture', 'Tochigi'),
                    '10' => tc('Japanese Prefecture', 'Gunma'),
                    '11' => tc('Japanese Prefecture', 'Saitama'),
                    '12' => tc('Japanese Prefecture', 'Chiba'),
                    '13' => tc('Japanese Prefecture', 'Tokyo'),
                    '14' => tc('Japanese Prefecture', 'Kanagawa'),
                    '15' => tc('Japanese Prefecture', 'Niigata'),
                    '16' => tc('Japanese Prefecture', 'Toyama'),
                    '17' => tc('Japanese Prefecture', 'Ishikawa'),
                    '18' => tc('Japanese Prefecture', 'Fukui'),
                    '19' => tc('Japanese Prefecture', 'Yamanashi'),
                    '20' => tc('Japanese Prefecture', 'Nagano'),
                    '21' => tc('Japanese Prefecture', 'Gifu'),
                    '22' => tc('Japanese Prefecture', 'Shizuoka'),
                    '23' => tc('Japanese Prefecture', 'Aichi'),
                    '24' => tc('Japanese Prefecture', 'Mie'),
                    '25' => tc('Japanese Prefecture', 'Shiga'),
                    '26' => tc('Japanese Prefecture', 'Kyoto'),
                    '27' => tc('Japanese Prefecture', 'Osaka'),
                    '28' => tc('Japanese Prefecture', 'Hyogo'),
                    '29' => tc('Japanese Prefecture', 'Nara'),
                    '30' => tc('Japanese Prefecture', 'Wakayama'),
                    '31' => tc('Japanese Prefecture', 'Tottori'),
                    '32' => tc('Japanese Prefecture', 'Shimane'),
                    '33' => tc('Japanese Prefecture', 'Okayama'),
                    '34' => tc('Japanese Prefecture', 'Hiroshima'),
                    '35' => tc('Japanese Prefecture', 'Yamaguchi'),
                    '36' => tc('Japanese Prefecture', 'Tokushima'),
                    '37' => tc('Japanese Prefecture', 'Kagawa'),
                    '38' => tc('Japanese Prefecture', 'Ehime'),
                    '39' => tc('Japanese Prefecture', 'Kochi'),
                    '40' => tc('Japanese Prefecture', 'Fukuoka'),
                    '41' => tc('Japanese Prefecture', 'Saga'),
                    '42' => tc('Japanese Prefecture', 'Nagasaki'),
                    '43' => tc('Japanese Prefecture', 'Kumamoto'),
                    '44' => tc('Japanese Prefecture', 'Oita'),
                    '45' => tc('Japanese Prefecture', 'Miyazaki'),
                    '46' => tc('Japanese Prefecture', 'Kagoshima'),
                    '47' => tc('Japanese Prefecture', 'Okinawa'),
                ],

                'CH' => [
                    'AG' => tc('Swiss Canton', 'Aargau'),
                    'AI' => tc('Swiss Canton', 'Appenzell I. Rh.'),
                    'AR' => tc('Swiss Canton', 'Appenzell A. Rh.'),
                    'BE' => tc('Swiss Canton', 'Bern'),
                    'BL' => tc('Swiss Canton', 'Basel-Landschaft'),
                    'BS' => tc('Swiss Canton', 'Basel-Stadt'),
                    'FR' => tc('Swiss Canton', 'Fribourg'),
                    'GE' => tc('Swiss Canton', 'Geneva'),
                    'GL' => tc('Swiss Canton', 'Glarus'),
                    'GR' => tc('Swiss Canton', 'Graub??nden'),
                    'JU' => tc('Swiss Canton', 'Jura'),
                    'LU' => tc('Swiss Canton', 'Lucerne'),
                    'NE' => tc('Swiss Canton', 'Neuch??tel'),
                    'NW' => tc('Swiss Canton', 'Nidwalden'),
                    'OW' => tc('Swiss Canton', 'Obwalden'),
                    'SG' => tc('Swiss Canton', 'St. Gallen'),
                    'SH' => tc('Swiss Canton', 'Schaffhausen'),
                    'SO' => tc('Swiss Canton', 'Solothurn'),
                    'SZ' => tc('Swiss Canton', 'Schwyz'),
                    'TG' => tc('Swiss Canton', 'Thurgau'),
                    'TI' => tc('Swiss Canton', 'Ticino'),
                    'UR' => tc('Swiss Canton', 'Uri'),
                    'VD' => tc('Swiss Canton', 'Vaud'),
                    'VS' => tc('Swiss Canton', 'Valais'),
                    'ZG' => tc('Swiss Canton', 'Zug'),
                    'ZH' => tc('Swiss Canton', 'Zurich'),
                ],
            ];
            $comparer = new \Punic\Comparer($locale);
            foreach (array_keys($provinces) as $country) {
                switch ($locale . '@' . $country) {
                    case 'ja_JP@JP':
                        break;
                    default:
                        $comparer->sort($provinces[$country], true);
                        break;
                }
            }
            $provinces['UK'] = $provinces['GB'];
            $event = new \Symfony\Component\EventDispatcher\GenericEvent();
            $event->setArgument('provinces', $provinces);
            $event = Events::dispatch('on_get_states_provinces_list', $event);
            $this->localizedStatesProvinces[$locale] = $event->getArgument('provinces');
        }

        return $this->localizedStatesProvinces[$locale];
    }

    /**
     * Returns the name of a specified State/Province in a specified Country.
     *
     * @param string $code the State/Province code
     * @param string $country the Country code
     *
     * @return string|null returns the State/Province name (if found), or null if not found
     */
    public function getStateProvinceName($code, $country)
    {
        $all = $this->getAll();
        if (isset($all[$country]) && isset($all[$country][$code])) {
            return $all[$country][$code];
        } else {
            return null;
        }
    }

    /**
     * Returns a list of States/Provinces for a country.
     *
     * @param string $country the country code
     *
     * @return array|null if the Country is supported, the function returns an array (whose keys are the States/Provinces codes and the values are their names); returns null if $country is not supported
     */
    public function getStateProvinceArray($country)
    {
        $all = $this->getAll();
        if (isset($all[$country])) {
            return $all[$country];
        } else {
            return null;
        }
    }

    /**
     * Returns the list of US states.
     *
     * @deprecated Use getStateProvinceArray('US')
     *
     * @return array returns an array whose keys are the US State codes and the values are their names
     */
    public function getStates()
    {
        return $this->getStateProvinceArray('US');
    }

    /** Returns the list of Canadian provinces.
     * @deprecated Use getStateProvinceArray('CA')
     *
     * @return array returns an array whose keys are the Canadian Provinces codes and the values are their names
     */
    public function getCanadianProvinces()
    {
        return $this->getStateProvinceArray('CA');
    }
}
