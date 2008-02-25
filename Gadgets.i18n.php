<?php
/**
 * Internationalisation file for extension Gadgets.
 *
 * @addtogroup Extensions
 * @author Daniel Kinzler, brightbyte.de
 * @copyright © 2007 Daniel Kinzler
 * @licence GNU General Public Licence 2.0 or later
 */

$messages = array();

/** English
 * @author Daniel Kinzler, brightbyte.de
 */
$messages['en'] = array(
	#for Special:Version
	'gadgets-desc'      => 'Lets users select custom [[Special:Gadgets|CSS and JavaScript gadgets]] in their [[Special:Preferences|preferences]]',

	#for Special:Preferences
	'gadgets-prefs'     => 'Gadgets',
	'gadgets-prefstext' => 'Below is a list of special gadgets you can enable for your account. These gadgets are mostly based on JavaScript, so JavaScript has to be enabled in your browser for them to work. Note that these gadgets will have no effect on this preferences page.

Also note that these special gadgets are not part of the MediaWiki software, and are usually developed and maintained by users on your local wiki. Local administrators can edit available gadgets using [[MediaWiki:Gadgets-definition]] and [[Special:Gadgets]].',

	#for Special:Gadgets
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => "Below is a list of special gadgets users can enable on their preferences page, as defined by [[MediaWiki:Gadgets-definition]]. This overview provides easy access to the system message pages that define each gadget's description and code.",
	'gadgets-uses'      => 'Uses',
);

/** Amharic (አማርኛ)
 * @author Codex Sinaiticus
 */
$messages['am'] = array(
	'gadgets-prefs'     => 'ተጨማሪ መሣርያዎች',
	'gadgets-prefstext' => 'ከዚህ ታች አንዳንድ ተጨማሪ መሣርያ ወይም መኪናነት በዝርዝር ሊገኝ ይችላል። እነዚህ በደንብ እንዲሠሩ በኮምፒውተርዎ ላይ ጃቫ-ስክሪፕት እንዲኖር አስፈላጊነት ነው።

የዚህ ዊኪ መጋቢዎች [[MediaWiki:Gadgets-definition]]
እና [[Special:Gadgets]] በመጠቀም አዲስ መሣርያ ሊጨምሩ ይቻላል።',
	'gadgets'           => 'ተጨማሪ መሣርያዎች',
	'gadgets-title'     => 'ተጨማሪ መሣርያዎች',
	'gadgets-pagetext'  => 'ተጨማሪ መሣርያዎች ወይም መኪናዎች በየዊኪ ፕሮዤ የለያያሉ።

ተጨማሪ መሣሪያዎች ለማግኘት፣ ወደ [[Special:Preferences|ምርጫዎች]] ይሂዱ።

የዚህ ገጽ መራጃ በተለይ ለመጋቢዎችና አስተዳዳሪዎች ይጠቅማል።

በዚህ {{SITENAME}} የሚገኙት ተቸማሪ መሣርያዎች እነኚህ ናቸው፦',
	'gadgets-uses'      => 'የተጠቀመው ጃቫ-ስክሪፕት',
);

/** Aragonese (Aragonés)
 * @author Juanpabl
 */
$messages['an'] = array(
	'gadgets-prefs'     => 'Trastes',
	'gadgets-prefstext' => "Contino ye una lista de trastes espezials que puede fer serbir en a suya cuenta.
Como cuasi toz istos trastes son feitos en JavaScript, caldrá que tienga autibato JavaScript en o suyo nabegador ta que baigan bien. Pare cuenta que istos trastes no tendrán garra efeuto en ista pachina de preferenzias.

Pare cuenta tamién que istos trastes espezials no fan parte d'o software MediaWiki, y que gosan estar desembolicatos y mantenitos por usuarios d'a suya wiki local. Os almenistradors locals pueden editar os trastes disponibles fendo serbir [[MediaWiki:Gadgets-definition]] y [[Special:Gadgets]].",
	'gadgets'           => 'Trastes',
	'gadgets-title'     => 'Trastes',
	'gadgets-pagetext'  => "Contino ye una lista de trastes espezials que os usuarios pueden autibar en a suya pachina de preferenzias, como se define en [[MediaWiki:Gadgets-definition]].
Ista lista premite ir rapedament t'as pachinas de mensaches d'o sistema que definen a descripzión y o codigo de cada traste.",
	'gadgets-uses'      => 'Fa serbir',
);

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'gadgets-desc'      => 'يسمح للمستخدمين باختيار [[Special:Gadgets|إضافات سي إس إس وجافاسكريبت]] معدلة في [[Special:Preferences|تفضيلاتهم]]',
	'gadgets-prefs'     => 'إضافات',
	'gadgets-prefstext' => 'بالأسفل قائمة بالإضافات الخاصة التي يمكن إضافتها لحسابك.
هذه الإضافات مبنية على الأغلب على جافاسكريبت، لذا فالجافاسكريبت يجب أن تكون مفعلة في متصفحك لكي يعملوا.
لاحظ أن هذه الإضافات لن يكون لها أي تأثير على صفحة التفضيلات هذه.

أيضا لاحظ أن هذه الإضافات الخاصة ليست جزءا من برنامج ميدياويكي، وعادة يتم تطويرها وصيانتها بواسطة مستخدمين في الويكي المحلي الخاص بك. الإداريون المحليون يمكنهم تعديل الإضافات المتوفرة باستخدام [[MediaWiki:Gadgets-definition]] 
و [[Special:Gadgets]].',
	'gadgets'           => 'إضافات',
	'gadgets-title'     => 'إضافات',
	'gadgets-pagetext'  => 'بالأسفل قائمة بالإضافات الخاصة التي يمكن أن يقوم المستخدمون بتفعيلها على صفحة تفضيلاتهم، معرفة بواسطة [[MediaWiki:Gadgets-definition]].
هذا العرض يوفر دخولا سهلا لصفحات رسائل النظام التي تعرف كود ووصف كل إضافة.',
	'gadgets-uses'      => 'تستخدم',
);

/** Asturian (Asturianu)
 * @author Esbardu
 */
$messages['ast'] = array(
	'gadgets-desc'      => 'Permite a los usuarios seleicionar al gustu [[Special:Gadgets|accesorios CSS y JavaScript]] nes sos [[Special:Preferencies|preferencies]]',
	'gadgets-prefs'     => 'Accesorios',
	'gadgets-prefstext' => "Embaxo amuésase una llista de los accesorios especiales que pues activar pa la to cuenta.
Estos accesorios tán mayormente basaos en JavaScript, polo qu'has tener activáu ésti nel to navegador pa que funcionen.
Date cuenta de qu'estos accesorios nun tendrán efeutu nesta páxina de preferencies.

Has decatate tamién de que estos accesorios especiales nun son parte del software MediaWiki, y que normalmente son
desenrollaos y manteníos por usuarios de la to wiki llocal. Los alministradores llocales puen editar los accesorios disponibles usando [[Mediawiki:Gadgets-definition]] y [[Special:Gadgets]].",
	'gadgets'           => 'Accesorios',
	'gadgets-title'     => 'Accesorios',
	'gadgets-pagetext'  => 'Embaxo amuésase una llista de los accesorios especiales que los usuarios puen activar na so páxina de preferencies, según queden definíos por [[Mediawiki:Gadgets-definition]].
Esta visión xeneral proporciona un accesu fácil a les páxines de mensaxes del sistema que definen la descripción y el códigu de cada accesoriu.',
	'gadgets-uses'      => 'Usa',
);

/** Bulgarian (Български)
 * @author DCLXVI
 * @author Spiritia
 * @author Borislav
 */
$messages['bg'] = array(
	'gadgets-desc'      => 'Позволява на потребителите да избират и активират [[Special:Gadgets|CSS и JavaScript джаджи]] от своите [[Special:Preferences|настройки]]',
	'gadgets-prefs'     => 'Джаджи',
	'gadgets-prefstext' => 'По-долу е списъкът на специалните джаджи, които можете да активирате на своята потребителска сметка.
Тъй като почти всички джаджи са базирани на Джаваскрипт, трябва да го активирате на браузъра си, за да могат те да работят.
Имайте предвид, че тези джаджи няма да окажат влияние на тази страница с настройки.

Също така, джаджите не са част от софтуера МедияУики, и обикновено се разработват и поддържат от потребители в локалното уики. Локалните администратори могат да редактират наличните джаджи посредством [[MediaWiki:Gadgets-definition]] и [[Special:Gadgets]].',
	'gadgets'           => 'Джаджи',
	'gadgets-title'     => 'Джаджи',
	'gadgets-pagetext'  => 'По-долу е списъкът на специалните джаджи, които потребителите могат да активират чрез страницата си с настройки, както е указано на [[MediaWiki:Gadgets-definition]].
Този списък дава лесен достъп до страниците със системни съобщения, съдържащи описанието и кода на всяка джаджа.',
);

/** Bengali (বাংলা)
 * @author Zaheen
 * @author Bellayet
 */
$messages['bn'] = array(
	'gadgets-prefs'     => 'গ্যাজেটগুলি',
	'gadgets-prefstext' => 'নিচে কিছু বিশেষ গ্যাজেটের তালিকা দেওয়া হল, যেগুলি আপনি আপনার অ্যাকাউন্টের জন্য সক্রিয় করতে পারেন।
এই গ্যাজেটগুলি বেশিরভাগই জাভাস্ক্রিপ্ট-ভিত্তিক, তাই এগুলি কাজ করতে হলে আপনার ব্রাউজারে জাভাস্ক্রিপ্ট সক্রিয় থাকতে হবে।
লক্ষ্য করুন, এই গ্যাজেটগুলি এই পছন্দ পাতায় কোন প্রভাব ফেলবে না।

আরও লক্ষ্য করুন যে এই বিশেষ গ্যাজেটগুলি মিডিয়াউইকি সফটওয়্যারের অংশ নয়, এবং সাধারণত আপনার স্থানীয় উইকির ব্যবহারকারীরা এগুলি তৈরি করেন ও রক্ষণাবেক্ষণ করেন। স্থানীয় প্রশাসকেরা লভ্য গ্যাজেটগুলি [[MediaWiki:Gadgets-definition]] এবং [[Special:Gadgets]]-এর সাহায্যে সম্পাদনা করতে পারেন।',
	'gadgets'           => 'গ্যাজেটগুলি',
	'gadgets-title'     => 'গ্যাজেট',
	'gadgets-uses'      => 'ব্যবহারসমূহ',
);

/** Catalan (Català)
 * @author SMP
 */
$messages['ca'] = array(
	'gadgets-prefstext' => "A continuació teniu una llista de «gadgets» especials que podeu activar al vostre compte.
La majoria d'aquests gadgets fan servir JavaScript, per tant haureu de tenir un navegador que funcioni amb aquest llenguatge activat per a que vos funcionin.
Tingueu en compte que els gadgets no funcionaran en aquesta pàgina.

També cal que tingueu present que aquests gadgets especials no formen part del programa MediaWiki i que acostumen a estar fets i mantinguts per usuaris del wiki local. Els administradors del wiki poden editar els gadgets disponibles a [[Special:Gadgets]].",
	'gadgets-pagetext'  => 'A continuació teniu una llista de «gadgets» especials que qualsevol usuari pot activar a la seva pàgina de preferències i que estan definits per [[MediaWiki:Gadgets-definition]].
Aquesta llista us permet un fàcil accés a les pàgines del sistema que defineixen el codi de cada gadget.',
	'gadgets-uses'      => 'Usa',
);

/** Czech (Česky)
 * @author Danny B.
 * @author Li-sung
 */
$messages['cs'] = array(
	'gadgets-desc'      => 'Uživatelé si mohou vybrat javaskriptové udělátko ve svém [[Special:Preferences|nastavení]].',
	'gadgets-prefs'     => 'Udělátka',
	'gadgets-prefstext' => 'Níže je přehled speciálních udělátek, které si můžete ve svém účtu zapnout.
Tato udělátka jsou převážně založena na JavaScriptu, takže pro jejich funkčnost je nutné mít ve svém prohlížeči JavaScript zapnutý. 
Udělátka nejsou aplikována na této stránce nastavení. 

Uvědomte si také, že speciální udělátka nejsou součástí softwaru MediaWiki a&nbsp;jsou vytvářena a&nbsp;spravována uživateli této wiki. Místní správci mohou editovat udělátka prostřednictvím [[MediaWiki:Gadgets-definition]] a&nbsp;[[Special:Gadgets]].',
	'gadgets'           => 'Udělátka',
	'gadgets-title'     => 'Udělátka',
	'gadgets-pagetext'  => 'Níže je přehled speciálních udělátek, která si uživatelé mohou zapnout ve svém nastavení. Seznam lze upravovat na [[MediaWiki:Gadgets-definition]].
Tento přehled poskytuje jednoduchý přístup k&nbsp;systémovým hlášením, která definují zdrojový kód a&nbsp;popis každého udělátka.',
	'gadgets-uses'      => 'používá',
);

/** Danish (Dansk)
 * @author Morten LJ
 */
$messages['da'] = array(
	'gadgets-prefs'     => 'Gadget',
	'gadgets-prefstext' => 'Nedenstående er en liste over de gadgets som du kan aktivere for din brugerkonto. Da disse gadgets hovedsageligt er baseret på JavaScript skal du slå JavaScript til i din browser for at få dem til at virke. Bemærk at disse gadgets ikke vil have nogen effekt på denne side (indstillinger).

Bemærk også at disse specielle gadgets ikke er en del af MediaWiki-softwaren og at de typisk bliver vedligeholdt af brugere på din lokale wiki. Lokale administratorer kan redigere tilgængelige gadgets med [[MediaWiki:Gadgets-definition]] og [[Speciel:Gadgets]].',
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => 'Nedenstående er en liste med de specielle gadgets som brugere kan aktivere i deres indstillinger som defineret i [[MediaWiki:Gadgets-definition]]. Denne oversigtsside giver simpel adgang til de systembeskeder som definerer hver gadgets beskrivelse og kode.',
	'gadgets-uses'      => 'Brugere',
);

/** German (Deutsch)
 * @author Daniel Kinzler, brightbyte.de
 */
$messages['de'] = array(
	'gadgets-desc'      => 'Ermöglicht Benutzern, in ihren [[{{#special:preferences}}|persönlichen Einstellungen]] vordefinierte [[Special:Gadgets|CSS- und JavaScript-Gadgets]] zu aktivieren',
	'gadgets-prefstext' => 'Liste von speziellen Gadgets die für jeden Benutzer aktiviert werden können.
Die Gadgets basieren zumeinst auf JavaScript, daher muss JavaScript im Browser aktiviert sein, damit sie funktionieren.
Die Gadgets funktionieren allerdings nicht auf dieser Seite mit persönlichen Einstellungen.

Ausserdem ist zu beachten, dass diese Gadgets im Allgemeinen nicht Teil von MediaWiki sind, sondern meist von
Benutzern des lokalen Wikis entwickelt und gewartet werden. Lokale Wiki-Administratoren können die Liste von
verfügbaren Gadgets über die Seiten [[MediaWiki:Gadgets-definition]] und [[Special:Gadgets]] bearbeiten',
	'gadgets-pagetext'  => 'Liste von speziellen Gadgets, die für jeden Benutzer verfügbar sind, wie in [[MediaWiki:Gadgets-definition]] definiert.
Diese Übersicht bietet direkten Zugang zu den Systemnachrichten, die die Beschreibung sowie den Programmcode jedes
Gadgets enthalten.',
	'gadgets-uses'      => 'Benutzt',
);

/** Greek (Ελληνικά)
 * @author Consta
 */
$messages['el'] = array(
	'gadgets-prefs'    => 'Συσκευές',
	'gadgets'          => 'Συσκευές',
	'gadgets-title'    => 'Συσκευές',
	'gadgets-pagetext' => 'Παρακάτω βρίσκεται μια λίστα με τις ειδικές συσκευές χρηστών που επιτρέπονται στη σελίδα προτιμήσεών σας, όπως καθορίζεται από το [[MediaWiki:Gadgets-definition]].
Αυτή η επισκόπηση παρέχει εύκολη πρόσβαση στις σελίδες μηνυμάτων του συστήματος που καθορίζουν την περιγραφή και τον κώδικα κάθε συσκευής.',
	'gadgets-uses'     => 'Χρήσεις',
);

/** Extremaduran (Estremeñu)
 * @author Better
 */
$messages['ext'] = array(
	'gadgets-uses' => 'Usus',
);

/** فارسی (فارسی)
 * @author Huji
 */
$messages['fa'] = array(
	'gadgets-desc'      => 'به کاربرها امکان انتخاب ابزارهای شخصی CSS و JavaScript را از طریق صفحهٔ [[Special:Preferences|ترجیحات]] می‌دهد',
	'gadgets-prefs'     => 'ابزارها',
	'gadgets-prefstext' => 'در زیر فهرستی از ابزارهای ویژه‌ای که می‌توانید برای حساب کاربری‌تان فعال کنید را می‌بینید.
این ابزارها در بیشتر موارد مبتنی بر جاوااسکریپت هستند، پس برای استفاده از آن‌ها باید جاوااسکرپیت را در مرورگر خودتان فعال کنید.
توجه کنید که این ابزارها نمی‌تواند صفحهٔ ترجیحات را تغییر دهند.

دقت داشته باشد که این ابزارها جزئی از نرم‌افزار مدیاویکی نیستند، و معمولاً توسط کاربران هر ویکی ساخته و نگهداری می‌شوند. مدیران هر ویکی می‌توانند با استفاده از صفحه‌های [[MediaWiki:Gadgets-definition]] و [[Special:Gadgets]] به ویرایش ابزارها بپردازند.',
	'gadgets'           => 'ابزارها',
	'gadgets-title'     => 'ابزارها',
	'gadgets-pagetext'  => 'در زیر فهرستی از ابزارهای ویژه‌ای که کاربران می‌توانند از طریق صفحهٔ ترجیحاتشان فعال کنند می‌بینید، که مطابق آن چه است که در [[MediaWiki:Gadgets-definition]] تعریف شده‌است.
این خلاصه کمک می‌کند که به صفحه‌های پیغام سیستمی که توضیحات و کد هر ابزار را شامل می‌شوند به راحتی دست پیدا کنید.',
	'gadgets-uses'      => 'کاربردها',

);

/** Finnish (Suomi)
 * @author Crt
 * @author Nike
 */
$messages['fi'] = array(
	'gadgets-prefs'     => 'Pienoisohjelmat',
	'gadgets-prefstext' => 'Alla on lista pienoisohjelmista, joita käyttäjät voivat ottaa käyttöön. Nämä pienoisohjelmat pohjautuvat usein JavaScriptiin, joten toimiakseen selaimessasi pitää olla JavaScript käytössä.

Huomio myös, että nämä pienoisohjelmat eivät ole osa MediaWiki-ohjelmistoa – tavallisesti niitä kehittävät ja ylläpitävät paikallisen wikin käyttäjät. Paikalliset ylläpitäjät voivat muokata saatavilla olevia pienoisohjelmia sivuilla [[MediaWiki:Gadgets-definition]] ja [[Special:Gadgets]].',
	'gadgets'           => 'Pienoisohjelmat',
	'gadgets-title'     => 'Pienoisohjelmat',
	'gadgets-pagetext'  => 'Alla on lista pienoisohjelmista, joita käyttäjät voivat ottaa käyttöön asetussivulta. Pienoisohjelmat on määritetty sivulla [[MediaWiki:Gadgets-definition]].

Tämä lista antaa helpon pääsyn järjestelmäviesteihin, jotka sisältävät pienoisohjelmien kuvauksen ja koodin.',
	'gadgets-uses'      => 'Käyttää',
);

/** French (Français)
 * @author Sherbrooke
 * @author Urhixidur
 * @author Grondin
 */
$messages['fr'] = array(
	'gadgets-desc'      => 'Laisse aux utilisateurs les [[Special:Gadgets|gadgets CSS et Javascripts]] personnalisés dans leurs [[Special:Preferences|préférences]]',
	'gadgets-prefs'     => 'Gadgets',
	'gadgets-prefstext' => 'Ci-dessous apparaît une liste de gadgets que vous pouvez activer pour votre compte. Ils font appel à [[JavaScript]], lequel doit donc être activé pour votre navigateur Web.

Ils n’ont aucune incidence sur cette page de préférences. De plus, ils sont généralement développés et maintenus sur ce wiki. Les administrateurs peuvent modifier les gadgets en passant par [[MediaWiki:Gadgets-definition]] et [[Special:Gadgets]].',
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => "Ci-dessous apparaît une liste de gadgets que les utilisateurs peuvent activer dans leur [[Special:Preferences|page de préférences]], tel que défini dans ''[[MediaWiki:Gadgets-definition]]''. Ce survol donne un accès rapide aux pages de messages système qui définissent chaque description et chaque code des gadgets.",
	'gadgets-uses'      => 'Utilise',
);

/** Franco-Provençal (Arpetan)
 * @author ChrisPtDe
 */
$messages['frp'] = array(
	'gadgets-desc'      => 'Lèsse ux utilisators la possibilitât de chouèsir/cièrdre los [[Special:Gadgets|outils CSS et JavaScript]] pèrsonalisâs dens lors [[Special:Preferences|prèferences]].',
	'gadgets-prefs'     => 'Outils',
	'gadgets-prefstext' => 'Ce-desot aparêt una lista d’outils que vos pouede activar por voutron compto.
Font apèl a [[JavaScript]], dêt vêr étre activâ por voutron navigator Malyâjo.
Ils ont gins d’enfluence sur ceta pâge de prèferences.

Et pués, sont g·ènèralament dèvelopâs et mantegnus sur ceti vouiqui. Los administrators pôvont modifiar los outils en passent per [[MediaWiki:Gadgets-definition]] et [[Special:Gadgets]].',
	'gadgets'           => 'Outils',
	'gadgets-title'     => 'Outils',
	'gadgets-pagetext'  => "Ce-desot aparêt una lista d’outils que los utilisators pôvont activar dens lor [[Special:Preferences|pâge de prèferences]], tâl que dèfeni dens ''[[MediaWiki:Gadgets-definition]]''.
Ceti survôlo balye un accès rapido a les pâges de mèssâjos sistèmo que dèfenéssont châque dèscripcion et châque code des outils.",
	'gadgets-uses'      => 'Utilise',
);

/** Galician (Galego)
 * @author Alma
 * @author Xosé
 */
$messages['gl'] = array(
	'gadgets-prefs'     => 'Trebellos',
	'gadgets-prefstext' => 'Embaixo hai unha lista de trebellos especiais que pode activar para a súa conta.
A maioría destes trebellos baséanse en JavaScript, así que ten que ter o JavaScript activado no seu navegador para que funcionen.
Teña en conta que estes trebellos non funcionarán nesta páxina de preferencias.

Teña tamén en conta que estes trebellos especiais non son parte do software de MediaWiki e que os crean e manteñen
os usuarios no seu wiki local. Os administradores locais poden editar os trebellos disponíbeis mediante [[MediaWiki:Gadgets-definition]] e [[Special:Gadgets]].',
	'gadgets'           => 'Trebellos',
	'gadgets-title'     => 'Trebellos',
	'gadgets-pagetext'  => 'Embaixo hai unha listaxe dos trebellos especiais que os usuarios poden habilitar nas súa páxina de preferencias, tal e como se define en [[MediaWiki:Gadgets-definition]].
Este panorama xeral é de doado acceso ao sistema das páxinas de mensaxes que define cada descrición e código dos trebellos.',
	'gadgets-uses'      => 'Usos',
);

/** Gujarati (ગુજરાતી)
 * @author Dsvyas
 */
$messages['gu'] = array(
	'gadgets-prefs'     => 'યંત્ર/યંત્રો, સાધન/સાધનો',
	'gadgets-prefstext' => "નીચે એવા વિશેષ સાધનોની યાદી નીચે આપી છે જે તમે તમારા ખાતામાં ઍનેબલ કરી શકો છો.

આ સાધનો મહદ્ અંશે જાવા સ્ક્રિપ્ટ આધારિત છે માટે તે યોગ્ય રીતે કામ કરે તે માટે આપના બ્રાઉઝરમાં જાવા સ્ક્રિપ્ટ ઍનેબલ કરેલી હોવી જરૂરી છે.

એ બાબત નોંધમાં લેશો કે આ સાધનોની અસર તમારા 'મારી પસંદ'ના પાના ઉપર થશે નહી.



એ વાત પણ ધ્યાનમાં રાખશો કે આ વિશેષ સાધનો મિડિયા વિકિ સૉફ્ટવેરનો ભાગ નથી, સામાન્ય રીતે તે આપના સ્થાનીક વિકિના સદસ્યો દ્વારા વિકસાવવામાં આવ્યા હોય છે અને તેઓજ તેનું ધ્યાન રાખે છે. સ્થાનિક પ્રબંધકો [[MediaWiki:Gadgets-definition]] અને [[Special:Gadgets]] નો ઉપયોગ કરીને આ સાધનોમાં ફેરફાર કરી શકે છે.",
	'gadgets'           => 'યંત્ર/યંત્રો, સાધન/સાધનો',
	'gadgets-title'     => 'યંત્ર/યંત્રો, સાધન/સાધનો',
	'gadgets-pagetext'  => "વિશેષ સાધનોની યાદી નીચે આપી છે જેમાથી જરૂરીયાત પ્રમાણેના સાધનો ઉપયોગકર્તા તેમના 'મારી પસંદ' પાના ઉપર ઍનેબલ કરી શકે છે ([[MediaWiki:Gadgets-definition]]મા વર્ણવ્યા મુજબ).

આ નિરિક્ષણથી સહેલાઇથી સિસ્ટમ સંદેશા વાળા પાના ખોલી શકશો જ્યાં દરેક સાધનનં વર્ણન અને તેનો કૉડ આપેલો છે.",
	'gadgets-uses'      => 'ઉપયોગો',
);

/** Hebrew (עברית) */
$messages['he'] = array(
	'gadgets-prefs'     => 'סקריפטים',
	'gadgets-prefstext' => 'להלן רשימה של סקריפטים שתוכלו להתקין בחשבון שלכם.
הסקריפטים מבוססים ברובם על שפת JavaScript, ולכן יש לאפשר אותה בדפדפן כדי שהם יעבדו.
שימו לב שלא תהיה לסקריפטים כל השפעה על דף ההעדפות הזה.

כמו כן, הסקריפטים אינם חלק מתוכנת מדיה־ויקי, והם בדרך כלל מפותחים ומתוחזקים על ידי משתמשים
באתר זה. מפעילי המערכת יכולים לערוך את רשימת הסקריפטים האפשריים תוך שימוש ב[[MediaWiki:Gadgets-definition|הודעת המערכת]] ו[[Special:Gadgets|הדף המיוחד]] המתאימים.',
	'gadgets'           => 'סקריפטים',
	'gadgets-title'     => 'סקריפטים',
	'gadgets-pagetext'  => 'זוהי רשימה של סקריפטים שמשתמשים יכולים להתקין באמצעות דף ההעדפות שלהם, כפי שהוגדרו ב[[MediaWiki:Gadgets-definition|הודעת המערכת המתאימה]].
מכאן ניתן לגשת בקלות לדפי הודעות המערכת שמגדירים את התיאור והקוד של כל סקריפט.',
	'gadgets-uses'      => 'משתמש בדפים',
);

/** Croatian (Hrvatski)
 * @author SpeedyGonsales
 */
$messages['hr'] = array(
	'gadgets-prefs'     => "JS ekstenzije (''gadgets'')",
	'gadgets-prefstext' => 'Slijedi popis posebnih ekstenzija koje možete omogućiti.
One su većinom napisane u JavaScriptu, stoga JavaScript mora biti omogućen u vašem web-pregledniku da bi ektenzije radile.
Nijedna ektenzija nema učinka na stranicu s postavkama.

Ove posebne ekstenzije nisu dio MediaWiki softvera, najčešće su razvijane i održavane od suradnika na lokalnom wikiju.
Lokalni administratori mogu uređivati dostupne ekstenzije putem [[MediaWiki:Gadgets-definition]] i [[Special:Gadgets]].',
	'gadgets'           => "JS ekstenzije (''gadgets'')",
	'gadgets-title'     => "JS ekstenzije (''gadgets'')",
	'gadgets-pagetext'  => 'Slijedi popis posebnih JavaScript ekstenzija koje suradnici mogu omogućiti u svojim postavkama, kako je definirano stranicom [[MediaWiki:Gadgets-definition]].
Ovaj pregled omogućuje lak pristup porukama sustava koje opisuju ekstenzije i njihov kod.',
	'gadgets-uses'      => 'Koristi',
);

/** Upper Sorbian (Hornjoserbsce)
 * @author Michawiki
 */
$messages['hsb'] = array(
	'gadgets-desc'      => 'Zmóžnja wužiwarjam swójske [[Special:Gadgets|přisłuški za CSS a JavaScript]] w jich [[Special:Preferences|nastajenjach]] wubrać',
	'gadgets-prefs'     => 'Specialne funkcije',
	'gadgets-prefstext' => 'Deleka je lisćina specialnych funkcijow, kotrež móžeš za swoje wužiwarske konto aktiwizować. Tute specialne funkcije zwjetša na JavaScripće bazěruja, tohodla dyrbi JavaScript we wobhladowaku aktiwizowany być, zo bychu fungowali.
Wobkedźbuj tež, zo so tute specialne funkcije na stronje z twojimi wosobinskimi nastajenjemi njewuskutkuja.

Nimo toho wobkedźbuj, zo tute specialne funkcije nejsu dźěl softwary MediaWiki a so zwjetša wot wužiwarjow na swojim lokalnym wikiju wuwiwaja a wothladuja. Lokalni administratorojo móža lisćinu k dispoziciji stejacych specialnych funkcijow přez [[MediaWiki:Gadgets-definition]] a [[Special:Gadgets]] wobdźěłać.',
	'gadgets'           => 'Specialne funkcije',
	'gadgets-title'     => 'Specialne funkcije',
	'gadgets-pagetext'  => 'Deleka je lisćina specialnych funkcijow, kotrež wwužiwarjo móža na swojej stronje nastajenjow, kaž přez [[MediaWiki:Gadgets-definition]] definowane.
Tutón přehlad skići lochki přistup k systemowym zdźělenkam, kotrež pomjenjowanje a kod kóždeje specialneje funkcije wobsahuja.',
	'gadgets-uses'      => 'Wužiwa',
);

/** Hungarian (Magyar)
 * @author Tgr
 * @author Bdanee
 */
$messages['hu'] = array(
	'gadgets-desc'      => 'A felhasználók saját [[Special:Gadgets|CSS és JavaScript eszközöket]] választhatnak ki a [[Special:Preferences|beállításaiknál]]',
	'gadgets-prefs'     => 'Segédeszközök',
	'gadgets-prefstext' => 'Itt tudod be- és kikapcsolni a helyi segédeszközöket. Ezek az eszközök nem részei a MediaWiki szoftvernek, általában a wiki felhasználói tartják karban őket. Az adminisztrátorok a [[MediaWiki:Gadgets-definition]] és a [[Special:Gadgets]] lapok segítségével tudják szerkeszteni a lenti listát.

A segédeszközök többsége javascriptet használ, így engedélyezned kell azt a böngésződben, hogy működjenek. (Ezen az oldalon a segédeszközök le vannak tiltva, hogy hiba esetén könnyen ki tudd őket kapcsolni.)',
	'gadgets'           => 'Segédeszközök',
	'gadgets-title'     => 'Segédeszközök',
	'gadgets-pagetext'  => 'Itt látható azon segédeszközök listája, amiket a felhasználók bekapcsolhatnak a beállításaiknál. A lista a [[MediaWiki:Gadgets-definition]] lapon módosítható. Az egyes segédeszközöknél fel vannak sorolva azok a lapok, amelyek az eszköz leírását, illetve kódját tartalmazzák.',
	'gadgets-uses'      => 'Kód',
);

/** Italian (Italiano)
 * @author BrokenArrow
 */
$messages['it'] = array(
	'gadgets-desc'      => 'Consente agli utenti di selezionare [[Special:Gadgets|accessori CSS e JavaScript]] nelle proprie [[Special:Preferences|preferenze]]',
	'gadgets-prefs'     => 'Accessori',
	'gadgets-prefstext' => "Di seguito viene presentata una lista di accessori speciali (''gadget'') che è possibile abilitare per il proprio account.
La maggior parte di questi accessori è basata su JavaScript, è quindi necessario abilitare JavaScript nel proprio browser perché funzionino correttamente. Si noti che gli accessori non hanno alcun effetto in questa pagina di preferenze.

Inoltre, si noti che questi accessori speciali non sono compresi nel software MediaWiki e vengono di solito realizzati e gestiti dagli utenti di ciascun sito wiki. Gli amministratori del sito possono modificare la lista degli accessori disponibili tramite le pagine [[MediaWiki:Gadgets-definition]] e [[Speciale:Accessori]]",
	'gadgets'           => 'Accessori',
	'gadgets-title'     => 'Accessori',
	'gadgets-pagetext'  => "Di seguito viene presentato un elenco di accessori (''gadget'') che gli utenti possono abilitare sulla propria pagina delle preferenze, seguendo le definizioni riportate in [[MediaWiki:Gadgets-definition]]. Questa panoramica fornisce un comodo meccanismo per accedere ai messaggi di sistema nei quali sono definiti la descrizione e il codice di ciascun accessorio.",
	'gadgets-uses'      => 'Utilizza',
);

/** Japanese (日本語)
 * @author JtFuruhata
 */
$messages['ja'] = array(
	'gadgets-desc'      => '[[{{ns:special}}:Gadgets|カスタムCSSやJavaScriptのガジェット]]を利用者が[[{{ns:Special}}:Preferences|{{int:preferences}}]]から選択できる',
	'gadgets-prefs'     => 'ガジェット',
	'gadgets-prefstext' => '下のリストはあなたのアカウントで利用できるガジェットの一覧です。これらのガジェットはほとんどがJavaScriptベースのため、その動作にはブラウザ設定でJavaScriptを有効にする必要があります。ただし、ガジェットが{{int:preferences}}ページ上では動作しないことも覚えておいてください。

また、これらのガジェットは MediaWiki ソフトウェアの一部ではなく、開発とメンテナンスは通常そのウィキのユーザによって行われていることにも注意してください。管理者は[[MediaWiki:Gadgets-definition]] や [[Special:Gadgets]]を編集することにより、利用可能なガジェットを指定することが可能です。',
	'gadgets'           => 'ガジェット',
	'gadgets-title'     => 'ガジェット',
	'gadgets-pagetext'  => '以下のリストは、[[MediaWiki:Gadgets-definition]] 上で定義された、ユーザが{{int:preferences}}ページにて利用可能にすることができるガジェットの一覧です。この概略はガジェットの説明やプログラムコードを定義しているシステムメッセージページへの簡単なアクセスも提供します。',
	'gadgets-uses'      => '利用するファイル',
);

/** Kazakh (Arabic script) (‫قازاقشا (تٴوتە)‬) */
$messages['kk-arab'] = array(
	'gadgets-prefs'     => 'قاجەت قۇرالدار',
	'gadgets-prefstext' => 'تومەندە ٴوز تىركەلگىڭىزدە قوسا الاتىن ارناۋلى قاجەت قۇرالدار ٴتىزىمى بەرىلەدى.
وسى قاجەت قۇرالدار كوبىنەسە JavaScript امىرلەرىنە نەگىزدەلىنەدى, سوندىقتان بۇلار جۇمىس ىستەۋى ٴۇشىن شولعىشىڭىزدا JavaScript قوسىلعان بولۋى كەرەك.
بۇل باپتاۋ بەتىنە وسى قاجەت قۇرالدار اسەر ەتپەيتىنىڭ ەسكەرىڭىز.

تاعى دا ەسكەرىڭىز: وسى قاجەت قۇرالدار MediaWiki باعدارلاماسىنىڭ بولىگى ەمەس, جانە دە بۇلاردى جايشىلىقتا جەرگىلىكتى ۋىيكىيدىڭ قاتىسۋشىلارى دامىتادى جانە قوشتايدى.
جەرگىلىكتى اكىمشىلەر جەتىمدى قاجەت نارسە ٴتىزىمىن [[{{ns:mediawiki}}:Gadgets-definition]] جانە [[{{ns:special}}:Gadgets]] بەتتەرى ارقىلى
وڭدەي الادى.',
	'gadgets'           => 'قاجەت قۇرالدار',
	'gadgets-title'     => 'قاجەت قۇرالدار',
	'gadgets-pagetext'  => 'تومەندە ارناۋلى قاجەت قۇرالدار ٴتىزىمى بەرىلەدى. [[{{ns:mediawiki}}:Gadgets-definition]] بەتىندە انىقتالعان قاجەت قۇرالداردى قاتىسۋشىلار ٴوزىنىڭ باپتاۋىندا قوسا الادى.
بۇل شولۋ بەتى ارقىلى ٴاربىر قاجەت قۇرالدىڭ سىيپاتتاماسى مەن ٴامىرىن انىقتايتىن جۇيە حابار بەتتەرىنە جەڭىل قاتىناي الاسىز.',
	'gadgets-uses'      => 'قولدانۋداعىلار',
);

/** Kazakh (Cyrillic) (Қазақша (Cyrillic)) */
$messages['kk-cyrl'] = array(
	'gadgets-prefs'     => 'Қажет құралдар',
	'gadgets-prefstext' => 'Төменде өз тіркелгіңізде қоса алатын арнаулы қажет құралдар тізімі беріледі.
Осы қажет құралдар көбінесе JavaScript әмірлеріне негізделінеді, сондықтан бұлар жұмыс істеуі үшін шолғышыңызда JavaScript қосылған болуы керек.
Бұл баптау бетіне осы қажет құралдар әсер етпейтінің ескеріңіз.

Тағы да ескеріңіз: осы қажет құралдар MediaWiki бағдарламасының бөлігі емес, және де бұларды жайшылықта жергілікті уикидің қатысушылары дамытады және қоштайды.
Жергілікті әкімшілер жетімді қажет нәрсе тізімін [[{{ns:mediawiki}}:Gadgets-definition]] және [[{{ns:special}}:Gadgets]] беттері арқылы
өңдей алады.',
	'gadgets'           => 'Қажет құралдар',
	'gadgets-title'     => 'Қажет құралдар',
	'gadgets-pagetext'  => 'Төменде арнаулы қажет құралдар тізімі беріледі. [[{{ns:mediawiki}}:Gadgets-definition]] бетінде анықталған қажет құралдарды қатысушылар өзінің баптауында қоса алады.
Бұл шолу беті арқылы әрбір қажет құралдың сипаттамасы мен әмірін анықтайтын жүйе хабар беттеріне жеңіл қатынай аласыз.',
	'gadgets-uses'      => 'Қолданудағылар',
);

/** Kazakh (Latin) (Қазақша (Latin)) */
$messages['kk-latn'] = array(
	'gadgets-prefs'     => 'Qajet quraldar',
	'gadgets-prefstext' => 'Tömende öz tirkelgiñizde qosa alatın arnawlı qajet quraldar tizimi beriledi.
Osı qajet quraldar köbinese JavaScript ämirlerine negizdelinedi, sondıqtan bular jumıs istewi üşin şolğışıñızda JavaScript qosılğan bolwı kerek.
Bul baptaw betine osı qajet quraldar äser etpeýtiniñ eskeriñiz.

Tağı da eskeriñiz: osı qajet quraldar MediaWiki bağdarlamasınıñ böligi emes, jäne de bulardı jaýşılıqta jergilikti wïkïdiñ qatıswşıları damıtadı jäne qoştaýdı.
Jergilikti äkimşiler jetimdi qajet närse tizimin [[{{ns:mediawiki}}:Gadgets-definition]] jäne [[{{ns:special}}:Gadgets]] betteri arqılı
öñdeý aladı.',
	'gadgets'           => 'Qajet quraldar',
	'gadgets-title'     => 'Qajet quraldar',
	'gadgets-pagetext'  => 'Tömende arnawlı qajet quraldar tizimi beriledi. [[{{ns:mediawiki}}:Gadgets-definition]] betinde anıqtalğan qajet quraldardı qatıswşılar öziniñ baptawında qosa aladı.
Bul şolw beti arqılı ärbir qajet quraldıñ sïpattaması men ämirin anıqtaýtın jüýe xabar betterine jeñil qatınaý alasız.',
	'gadgets-uses'      => 'Qoldanwdağılar',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'gadgets-prefs' => 'Gadgeten',
	'gadgets'       => 'Gadgeten',
	'gadgets-title' => 'Gadgeten',
	'gadgets-uses'  => 'Benotzt',
);

/** Limburgish (Limburgs)
 * @author Ooswesthoesbes
 * @author Matthias
 */
$messages['li'] = array(
	'gadgets-desc'      => 'Laat gebroekers [[special:Gadgets|CSS en JavaScripts]] activere in hun [[Special:Preferences|veurkeure]]',
	'gadgets-prefs'     => 'Biedènger',
	'gadgets-prefstext' => 'Hiej ónger staon de speciaal oetbreijinger diet geer veur uche gebroeker kintj insjakele.
De oetbreijinger zeen veurnamelik gebaseerdj op JavaScript, dus JavaScript mót veur uche browser ingesjakledj zeen óm die te laote wèrke.
De oetbreijinge höbbe gein invloedj op dees pazjena mit veurkeure.

Dees speciaal oetbreijinge zeen ouch gein óngerdeil van de MediaWiki-sofwaer en die mótte meistal óntwikkeldj en óngerhaoje waere door gebroekers van uche wiki. Beheerders kinne de besjikbare oetbreijinge aangaeve in [[MediaWiki:Gadgets-definition]] en [[Special:Gadgets]].',
	'gadgets'           => 'Oetbreijinger',
	'gadgets-title'     => 'Oetbreijinger',
	'gadgets-pagetext'  => 'Hiej ónger staon de speciaal oetbreijinger die gebroekers kinne insjakele via häöre veurkeure wie ingesteldj is in [[MediaWiki:Gadgets-definition]].
Dit euverzich bi-jtj uch einvoudige toegank toet de systeemtekspazjena wo de besjrieving en de programmacode van edere oetbreijing steit.',
	'gadgets-uses'      => 'Gebroek',
);

/** Lithuanian (Lietuvių)
 * @author Vpovilaitis
 */
$messages['lt'] = array(
	'gadgets-prefs'     => 'Priemonės',
	'gadgets-prefstext' => 'Žemiau yra sąrašas specialių priemonių, kurias jūs galite įjungti naudojimui.
Šios priemonės daugiausia yra sukurtos naudojant JavaScript, todėl JavaScript panaudojimas turi būti įjungtas jūsų brauseryje, kad jos veiktų.
Atsiminkite, kad šios priemonės neturi itakos jūsų nustatymų puslapiui.

Taip pat žinokite, kad šios specialios priemonės nėra MediaWiki programinės įrangos dalis ir yra sukurtos ir palaikomos jūsų lokalaus viki vartotojų. Lokalūs administratoriai gali redaguoti suteikiamų specialių priemonių sąrašą, naudojant puslapius [[MediaWiki:Gadgets-definition|priemonių aprašymas]] ir [[Special:Gadgets|priemonės]].',
	'gadgets'           => 'Priemonės',
	'gadgets-title'     => 'Priemonės',
	'gadgets-pagetext'  => 'Žemiau yra sąrašas specialių priemonių, kurias vartotojai gali įjungti savo nustatymo puslapyje, kurios apibrėžtos [[MediaWiki:Gadgets-definition|priemonių aprašyme]]. Ši apžvalga suteikia lengvą priėjimą prie sisteminių pranešimų puslapių, kuriuose apibrėžiamas kiekvienos priemonės trumpas aprašas ir kodas.',
	'gadgets-uses'      => 'Panaudojimai',
);

/** Marathi (मराठी)
 * @author Mahitgar
 */
$messages['mr'] = array(
	'gadgets-prefs' => 'उपकरण(गॅजेट)',
	'gadgets'       => 'सुविधा (गॅजेट)',
	'gadgets-title' => 'गॅजेट',
	'gadgets-uses'  => 'उपयोग',
);

/** Dutch (Nederlands)
 * @author SPQRobin
 * @author Siebrand
 */
$messages['nl'] = array(
	'gadgets-desc'      => 'Laat gebruikers [[Special:Gadgets|CSS en JavaScripts]] activeren in hun [[Special:Preferences|voorkeuren]]',
	'gadgets-prefs'     => 'Uitbreidingen',
	'gadgets-prefstext' => 'Hieronder staan de speciale uitbreidingen die u voor uw gebruiker kunt inschakelen.
De uitbreidingen zijn voornamelijk gebaseerd op JavaScript, dus JavaScript moet voor uw browser ingeschakeld zijn om ze te laten werken.
De uitbreidingen hebben geen invloed op deze pagina met voorkeuren.

Deze speciale uitbreidingen zijn ook geen onderdeel van de MediaWiki-software, en ze worden meestal ontwikkeld en onderhouden
door gebruikers van uw wiki. Beheerders kunnen de beschikbare uitbreidingen aangeven in [[MediaWiki:Gadgets-definition]] 
en [[Special:Gadgets]].',
	'gadgets'           => 'Uitbreidingen',
	'gadgets-title'     => 'Uitbreidingen',
	'gadgets-pagetext'  => 'Hieronder staan de speciale uitbreidingen die gebruikers kunnen inschakelen via hun voorkeuren, zoals ingesteld in [[MediaWiki:Gadgets-definition]].
Dit overzicht biedt eenvoudige toegang tot de systeemtekstpagina waar de beschrijving en de programmacode van iedere uitbreiding staat.',
	'gadgets-uses'      => 'Gebruikt',
);

/** Norwegian Nynorsk (‪Norsk (nynorsk)‬)
 * @author Eirik
 */
$messages['nn'] = array(
	'gadgets-desc'      => 'Lèt brukarane velje tilleggsfunksjonar med vanleg CSS og JavaScript i [[Special:Preferences|innstillingane sine]].',
	'gadgets-prefs'     => 'Tilleggsfunksjonar',
	'gadgets-prefstext' => 'Under finn du ei liste over tilleggsfunksjonar som du kan slå på på kontoen din. Desse tilleggsfunksjonane er for det meste baserte på JavaScript, så JavaScript må vere slått på i nettlesaren din for at dei skal verke. Merk at desse tilleggsfunksjonane ikkje har nokon effekt på denne innstillingssida.

Merk også at tilleggsfunksjonane ikkje er ein del av MediaWiki-programvaren, og at dei vanlegvis er utvikla og vedlikehaldne av brukarar på din lokale wiki. Lokale administratorar kan endre dei tilgjengelege tilleggsfunksjonane frå [[MediaWiki:Gadgets-definition]] og [[Special:Gadgets]].',
	'gadgets'           => 'Tilleggsfunksjonar',
	'gadgets-title'     => 'Tilleggsfunksjonar',
	'gadgets-pagetext'  => 'Under finn du ei liste over tilleggsfunksjonar som brukarane kan slå på på innstillingssidene sine, som oppgjeve i [[MediaWiki:Gadgets-definition]].
Dette oversynet gjev enkel tilgang til systemmeldingssidene som inneheld skildringa og koden til kvar enkelt tilleggsfunksjon.',
	'gadgets-uses'      => 'Brukar',
);

/** Norwegian (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'gadgets-desc'      => 'Lar brukere velge egendefinerte [[Special:Gadgets|CSS- og JavaScript-verktøy]] i [[Special:Preferences|innstillingene sine]]',
	'gadgets-prefs'     => 'Tilleggsfunksjoner',
	'gadgets-prefstext' => 'Under er en liste over tilleggsfunksjoner du kan slå på for kontoen din.
Disse funksjonene er for det meste basert på JavaScript, så du må ha dette slått på i nettleseren din for at de skal fungere.
Merk at funksjonene ikke vil ha noen innvirkning på denne innstillingssiden.

Merk også at disse verktøyene ikke er del av MediaWiki-programvaren, og vanligvis utvikles og vedlikeholdes av brukere på den lokale wikien. Lokale administratorer kan redigere tilgjengelig verktøy ved å bruke [[MediaWiki:Gadgets-definition]] og [[Special:Gadgets]].',
	'gadgets'           => 'Tilleggsfunksjoner',
	'gadgets-title'     => 'Tilleggsfunksjoner',
	'gadgets-pagetext'  => 'Under er en liste over tilleggsfunksjoner brukere kan slå på på sin innstillingsside, som definert av [[MediaWiki:Gadgets-definition]]. Denne oversikten gir lett tilgang til systembeskjedsidene som definerer hvert verktøys beskrivelse og kode.',
	'gadgets-uses'      => 'Bruk',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'gadgets-desc'      => 'Daissa als utilizaires los gadgets Javascripts personalizats dins lor [[Special:Preferences|preferéncias]]',
	'gadgets-prefs'     => 'Gadgets',
	'gadgets-prefstext' => "Çaijós apareis una lista de gadgets que podètz activar per vòstre compte. Fan apèl a JavaScript, deu doncas èsser activat per vòstre navegaire Web. An pas cap d'incidéncia sus aquesta pagina de preferéncias. E mai, son generalament desvolopats e mantenguts sus aqueste wiki. Los administrators pòdon modificar los gadgets en passant per [[MediaWiki:Gadgets-definition]] e [[Special:Gadgets]].",
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => "Çaijós apareis una lista de gadgets que los utilizaires pòdon activar dins lor pagina de preferéncias, coma definit dins ''[[MediaWiki:Gadgets-definition]]''. Aqueste susvòl dona un accès rapid a las paginas de messatges del sistèma que definisson cada descripcion e cada còde dels gadgets.",
	'gadgets-uses'      => 'Utiliza',
);

/** Polish (Polski)
 * @author Derbeth
 * @author Sp5uhe
 */
$messages['pl'] = array(
	'gadgets-desc'      => 'Pozwala użytkownikom wybrać [[Special:Gadgets|gadżety CSS i JavaScript]] na [[Special:Preferences|stronie preferencji]]',
	'gadgets-prefs'     => 'Gadżety',
	'gadgets-prefstext' => 'Poniżej znajduje się lista specjalnych gadżetów, które możesz włączyć dla swojego konta. Gadżety opierają się w większości na JavaScripcie, więc by działały musisz mieć w swojej przeglądarce włączony JavaScript. Pamiętaj, że nie będą one miały wpływu na tę stronę preferencji.

Gadżety nie są częścią oprogramowania MediaWiki i najprawdopodobniej zostały stworzone przez użytkowników tego wiki. Lokalni administratorzy mogą edytować dostępne gadżety używając stron [[MediaWiki:Gadgets-definition]] i [[Special:Gadgets]].',
	'gadgets'           => 'Gadżety',
	'gadgets-title'     => 'Gadżety',
	'gadgets-pagetext'  => 'Poniżej znajduje się lista specjalnych gadżetów, które użytkownicy mogą włączyć na swojej stronie preferencji, jak zdefiniowano to na stronie [[MediaWiki:Gadgets-definition]]. Ten przegląd daje łatwy dostęp do komunikatów systemu, które definiują opis i kod każdego z gadżetów.',
	'gadgets-uses'      => 'Użycie',
);

/** Piemontèis (Piemontèis)
 * @author Bèrto 'd Sèra
 */
$messages['pms'] = array(
	'gadgets-prefs'     => 'Component',
	'gadgets-prefstext' => "Ambelessì sota a-i é na lista ëd component ch'a peul vischesse ant sò cont personal. Sti component-sì a son dzortut basà ansima a JavaScript, donca a venta anans tut che JavaScript a sia avisch ant sò navigator, s'a veul che ij component a travajo. Ch'a ten-a present che sti component a l'han gnun efet ansima a la pàgina dij \"sò gust\", e che a son nen part dël programa MediaWiki. Për sòlit a resto dësvlupà e mantnù da dj'utent dla wiki andova chiel/chila as treuva adess. J'aministrator locaj a peulo regolé ij component disponibij ën dovrand le pàgine [[MediaWiki:Gadgets-definition|definission dij component]] e [[Special:Gadgets|component]].",
	'gadgets'           => 'Component',
	'gadgets-title'     => 'Component',
	'gadgets-pagetext'  => "Ambelessì sota a-i é na lista ëd component spessiaj che j'utent a peulo butesse avisch daspërlor, conforma a la [[MediaWiki:Gadgets-definition|definission dij component]]. Sta lista complessiva a smon na stra còmoda për rivé a le pàgine ëd messagi ëd sistema ch'a definisso descrission e còdes ëd vira component.",
	'gadgets-uses'      => 'a dòvra',
);

/** Pashto (پښتو)
 * @author Ahmed-Najib-Biabani-Ibrahimkhel
 */
$messages['ps'] = array(
	'gadgets-uses' => 'کارونې',
);

/** Portuguese (Português)
 * @author 555
 * @author Malafaya
 */
$messages['pt'] = array(
	'gadgets-desc'      => 'Permite aos utilizadores seleccionarem [[{{ns:special}}:Gadgets|"gadgets" JavaScript e CSS]] personalizados nas suas [[{{ns:special}}:Preferences|preferências]]',
	'gadgets-prefs'     => 'Gadgets',
	'gadgets-prefstext' => 'Segue-se uma lista de "gadgets" que podem ser ativados em sua conta.
Tais gadgets normalmente são baseados em JavaScript, o que faz com que seja necessário que o suporte a JavaScript esteja ativado em seu navegador para que os mesmos funcionem.
Note que os gadgets não possuem efeito nesta página (a página de preferências).

Note também que tais gadgets não são parte do software MediaWiki, geralmente sendo desenvolvidos e mantidos por usuários de sua wiki local. Administradores locais podem editar os gadgets disponíveis através da [[MediaWiki:Gadgets-definition]] e [[{{ns:special}}:Gadgets]].',
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => 'Segue-se uma lista de "gadgets" definidos em [[MediaWiki:Gadgets-definition]], que podem ser ativados por utilizadores através de suas páginas de preferências.
Esta visão geral proporciona um acesso fácil para as mensagens de sistema que definem as descrições e códigos de cada um dos gadgets.',
	'gadgets-uses'      => 'Utiliza',
);

/** Russian (Русский)
 * @author VasilievVV
 * @author Illusion
 * @author .:Ajvol:.
 */
$messages['ru'] = array(
	'gadgets-desc'      => 'Позволяет участникам выбирать в [[Special:Preferences|настройках]] CSS- и JavaScript-гаджеты, которые они хотят подключить',
	'gadgets-prefs'     => 'Гаджеты',
	'gadgets-prefstext' => 'Ниже приведён список специальных гаджетов, которые вы можете включить для своей учётной записи.
Эти гаджеты преимущественно основаны на JavaScript, поэтому вы должны включить JavaScript для того, чтобы они работали.
Учтите, что эти гаджеты не работают на странице настроек.

Также учтите, что эти гаджеты не являются частью MediaWiki, и обычно рарабатываются и обслуживаются
участниками вики. Администраторы могут изменять список гаджетов с помощью [[MediaWiki:Gadgets-definition]] 
и [[Special:Gadgets]].',
	'gadgets'           => 'Гаджеты',
	'gadgets-title'     => 'Гаджеты',
	'gadgets-pagetext'  => 'Ниже приведён список гаджетов, которые могут быть включены на странице [[Special:Preferences|настроек]].
Этот список позволяет легко просматривать сообщения, которые содержат определения гаджетов.',
	'gadgets-uses'      => 'Использует',
);

/** Yakut (Саха тыла)
 * @author HalanTul
 */
$messages['sah'] = array(
	'gadgets-desc'      => 'Бэйэлэрин [[Special:Preferences|туруорууларыгар]] кыттааччылар [[Special:Gadgets|CSS уонна JavaScript гаджеттары]] холбонуохтарын сөп.',
	'gadgets-prefs'     => 'Гаджеттар',
	'gadgets-prefstext' => 'Аллара аналлаах гаджеттар испииһэктэрэ көстөллөр. Балары бэйэҥ бэлиэтэммит ааккынан киирэн туһаныаххын сөп.
Бу үнүстүрүмүөннэр үксүлэрэ JavaScript көмөтүнэн үлэлииллэр, онон туһаныаххын баҕарар буоллаххына JavaScript холбоо.
Өйдөө, бу гаджеттар туроуорууларгын уларытар сирэйгэр үлэлээбэттэр.

Өссө биир өйдөө, бу гаджеттар MediaWiki сорҕото буолбатахтар, кинилэри кыттааччылар бэйэлэрэ айаллар уонна көрөллөр-истэллэр.  Администратардар гаджеттар испииһэктэрин [[MediaWiki:Gadgets-definition]] уонна [[Special:Gadgets]] көмөтүнэн уларытыахтарын сөп.',
	'gadgets'           => 'Гаджеттар',
	'gadgets-title'     => 'Гаджеттар',
	'gadgets-pagetext'  => 'Манна [[Special:Preferences|туруоруу]] сирэйигэр холбонуон сөптөөх гаджеттар испииһэктэрэ көрдөрүлүннэ.
Этот список позволяет легко просматривать сообщения, которые содержат определения гаджетов.',
	'gadgets-uses'      => 'Туһанар',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'gadgets-desc'      => 'Umožňuje používateľovi vybrať [[Special:Gadgets|CSS a JavaScriptové nástroje]] vo svojich [[Special:Preferences|nastaveniach]]',
	'gadgets-prefs'     => 'Nástroje',
	'gadgets-prefstext' => 'Dolu je zoznam špeciálych nástrojov, ktoré môžete zapnúť v rámci svojho účtu. Tieto nástroje sú zväčša založené na JavaScripte, takže aby fungovali, musíte mať v prehliadači zapnutý JavaScript. Nástroje nemajú vplyv na túto stránku nastavení. Tiež majte na pamäti, že tieto nástroje nie sú súčasťou MediaWiki a zvyčajne ich vyvíjajú a udržiavajú používatelia vašej lokálnej wiki. Lokálni správcovia môžu upraviť zoznam dostupných nástrojov na [[MediaWiki:Gadgets-definition]] a [[Special:Gadgets]].',
	'gadgets'           => 'Nástroje',
	'gadgets-title'     => 'Nástroje',
	'gadgets-pagetext'  => 'Dolu je zoznam špeciálych nástrojov, ktoré môžu používatelia zapnúť v rámci svojho účtu na svojej stránke nastavení. Tento zoznam definuje [[MediaWiki:Gadgets-definition]]. Tento prehľad poskytuje jednoduchý prístup k systémovým stránkam, ktoré definujú popis a kód každého z nástrojov.',
	'gadgets-uses'      => 'Použitia',
);

/** Seeltersk (Seeltersk)
 * @author Pyt
 */
$messages['stq'] = array(
	'gadgets-prefs'     => 'Gadgets',
	'gadgets-prefstext' => 'Lieste fon spezielle Gadgets do der foar älken Benutser aktivierd wäide konnen.
Do Gadgets basierje maastens ap Javascript, deeruum mout Javascript in dän Browser aktivierd weese, uumdät jo funktionierje.
Do Gadgets funktionierje oawers nit ap disse Siede mäd persöönelke Ienstaalengen.

Buutendät is tou beoachtjen, dät disse Gadgets in Algemeenen nit Paat fon MediaWiki sunt, man maast fon
Benutsere fon lokoale Wikis äntwikkeld un fersuurged wäide. Lokoale Wiki-Administratore konnen ju Lieste fon
ferföichboare Gadgets uur do Sieden [[MediaWiki:Gadgets-definition]] un [[Special:Gadgets]] beoarbaidje.',
	'gadgets'           => 'Gadgets',
	'gadgets-title'     => 'Gadgets',
	'gadgets-pagetext'  => 'Lieste fon spezielle Gadgets, do der foar älken Benutser ferföichboar sunt, as in [[MediaWiki:Gadgets-definition]] definierd.
Disse Uursicht bjut direkten Tougoang tou do Systemättergjuchte, do ju Beschrieuwenge as uk dän Programmcode fon
älk Gadget änthoolde.',
	'gadgets-uses'      => 'Benutsed',
);

/** Swedish (Svenska)
 * @author Lejonel
 * @author M.M.S.
 */
$messages['sv'] = array(
	'gadgets-desc'      => 'Låter användare aktivera personliga [[Special:Gadgets|CSS- och JavaScript-finesser]] genom sina [[Special:Preferences|inställningar]]',
	'gadgets-prefs'     => 'Finesser',
	'gadgets-prefstext' => 'Härunder är en lista över tilläggsfunktioner (finesser) som du kan aktivera för ditt konto.
De flesta funktionerna är baserade på JavaScript, så du måste ha JavaScript aktiverat i din webbläsare för att de ska fungera.
Notera att de här tilläggsfunktionerna inte kommer ha någon effekt den här inställningssidan.

Notera också att dessa finesser inte är en del av MediaWiki-programvaran. De är för det mesta utvecklade och underhållna av användare på den här wikin. Lokala administratörer kan redigera de tillgängliga funktionerna med hjälp av [[MediaWiki:Gadgets-definition]] och [[Special:Gadgets]].',
	'gadgets'           => 'Finesser',
	'gadgets-title'     => 'Finesser',
	'gadgets-pagetext'  => 'Härunder finns en lista över finesser (på engelska "gadgets") som användare kan aktivera i sina inställningar. Listan definieras av [[MediaWiki:Gadgets-definition]].
Den här översikten ger enkel åtkomst till de systemmeddelanden som definerar beskrivningarna och koden för de olika finesserna.',
	'gadgets-uses'      => 'Använder',
);

/** Telugu (తెలుగు)
 * @author Mpradeep
 * @author Veeven
 */
$messages['te'] = array(
	'gadgets-desc'      => 'వాడుకర్లను వారి [[Special:Preferences|అభిరుచుల]]లో  ప్రత్యేక [[Special:Gadgets|CSS మరియు జావాస్క్రిప్ట్ గాడ్జెట్లను]] ఎంచుకోనిస్తుంది',
	'gadgets-prefs'     => 'ఉపకరణాలు',
	'gadgets-prefstext' => 'ఈ దిగువ ఉన్న ప్రత్యేక ఉపకరణాల నుండి సభ్యులు తమకు కావలసినవి టిక్కు పెట్టి మీ ఖాతాకు వీటిని ఎనేబుల్ చేసుకొవచ్చు. ఈ ఉపకరణాలు జావాస్క్రిప్టుపై ఆధారపడి పనిచేస్తాయి కాబట్టి ఇవి సరిగా పనిచెయ్యాలంటే మీ బ్రౌజరులో జావాస్క్రిప్టును ఎనేబుల్ చేసి ఉండాలి. ఈ ఉపకరణాలు అభిరుచుల పేజీపై ఎటువంటి ప్రభావాన్ని కలుగజేయవని గమనించాలి.

అలాగే ఈ ప్రత్యేక ఉపకరణాలు మీడియావికీ సాఫ్టువేరులో భాగము కాదని గమనించాలి. వీటిని సాధారణంగా మీ స్థానిక వికీలోని సభ్యులే తయారుచేసి నిర్వహిస్తూ ఉంటారు. స్థానిక వికీ నిర్వాహకులు లభ్యమయ్యే ఉపకరణాలను [[మీడియావికీ:Gadgets-definition|మీడియావికీ:ఉపకరణాల నిర్వచన]] మరియు [[ప్రత్యేక:Gadgets|ప్రత్యేక:ఉపకరణాలు]] పేజీలను ఉపయోగించి మార్పులుచేర్పులు చేయవచ్చు.',
	'gadgets'           => 'ఉపకరణాలు',
	'gadgets-title'     => 'ఉపకరణాలు',
	'gadgets-pagetext'  => 'ఈ దిగువన ఉన్న ప్రత్యేక ఉపకరణాల నుండి సభ్యులు తమకు కావలసినవి తమ అభిరుచులు పేజీలోని ఉపకరణాల టాబులో టిక్కు పెట్టి ఎనేబుల్ చేసుకొనే అవకాశం ఉన్నది. వీటిని [[మీడియావికీ:Gadgets-definition|మీడియావికీ:ఉపకరణాల నిర్వచన]] పేజీలో నిర్వచించడం జరిగింది. ఈ చిన్న పరిచయం ఆయా ఉపకరణాల నిర్వచన మరియు కోడుకు సంబంధించిన మీడియావికీ సందేశాలకు సులువుగా చేరుకునేందుకు లింకులను సమకూర్చుతుంది.',
	'gadgets-uses'      => 'ఉపయోగించే ఫైళ్ళు',
);

/** Tajik (Тоҷикӣ)
 * @author Ibrahim
 */
$messages['tg'] = array(
	'gadgets-desc'  => 'Аз тариқи саҳифаи [[Special:Preferences|тарҷиҳот]] ба корбарон имконияти интихоби абзорҳои шахсии CSS ва JavaScript-ро медиҳад.',
	'gadgets-prefs' => 'Абзорҳо',
	'gadgets'       => 'Абзорҳо',
	'gadgets-title' => 'Абзорҳо',
	'gadgets-uses'  => 'Корбурдҳо',
);

/** Thai (ไทย)
 * @author Passawuth
 */
$messages['th'] = array(
	'gadgets-desc'  => 'อนุญาตให้ผู้ใช้เลือก [[Special:Gadgets|แกเจตที่สร้างโดยใช้ CSS และ จาวาสคริปต์]] ในหน้า [[Special:Preferences|การตั้งค่า]] ของพวกเขา',
	'gadgets-prefs' => 'แกเจต',
	'gadgets'       => 'แกเจต',
	'gadgets-title' => 'แกเจต',
	'gadgets-uses'  => 'การใช้',
);

/** Turkish (Türkçe)
 * @author Erkan Yilmaz
 */
$messages['tr'] = array(
	'gadgets-uses' => 'Kullanıyor',
);

/** Vietnamese (Tiếng Việt)
 * @author Vinhtantran
 * @author Meno25
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'gadgets-desc'      => 'Để các thành viên chọn những [[Special:Gadgets|công cụ đa năng]] đặc chế bằng CSS và JavaScript trong [[Special:Preferences|tùy chọn]]',
	'gadgets-prefs'     => 'Công cụ đa năng',
	'gadgets-prefstext' => 'Dưới đây là danh sách các công cụ đa năng đặc biệt mà bạn có thể kích hoạt cho tài khoản của mình.
Những công cụ này chủ yếu dựa trên JavaScript, do đó bạn phải kích hoạt JavaScript trong trình duyệt để các công cụ này hoạt động.
Chú ý rằng những công cụ đa năng này sẽ không có tác dụng trong trang tùy chọn cá nhân.

Cũng chú ý rằng những công cụ đặc biệt này không phải là một phần của phần mềm MediaWiki, mà thường được phát triển và bảo trì bởi những thành viên ở wiki ngôn ngữ của họ. Những quản lý ở từng ngôn ngữ có thể sửa đổi các công cụ đa năng có sẵn dùng [[MediaWiki:Gadgets-definition]] và [[Special:Gadgets]].',
	'gadgets'           => 'Công cụ đa năng',
	'gadgets-title'     => 'Công cụ đa năng',
	'gadgets-pagetext'  => 'Dưới đây là danh sách các công cụ đa năng đặc biệt mà thành viên có thể dùng tại trang tùy chọn cá nhân của họ, được định nghĩa tại [[MediaWiki:Gadgets-definition]]. Trang tổng quan này cung cấp cách tiếp cận dễ dàng đến trang các thông báo hệ thống để định nghĩa miêu tả và mã của từng công cụ.',
	'gadgets-uses'      => 'Sử dụng',
);

/** Volapük (Volapük)
 * @author Smeira
 */
$messages['vo'] = array(
	'gadgets-uses' => 'Gebs',
);

/** Yue (粵語)
 * @author Shinjiman
 */
$messages['yue'] = array(
	'gadgets-prefs'     => '小工具',
	'gadgets-prefstext' => '下面係一個特別小工具，你可以響你個戶口度啟用。
呢啲小工具多數都係基於JavaScript建造，如果要開佢哋，噉個瀏覽器嘅JavaScript就需要啟用咗先至用得到。
要留意嘅就係呢啲小工具響呢個喜好設定版度係無效果嘅。

亦都同時留意呢啲小工具嘅特別頁唔係MediaWiki軟件嘅一部份，通常都係由你本地嘅wiki度開發同維護。本地管理員可以響[[MediaWiki:Gadgets-definition]]同埋[[Special:Gadgets]]編輯可以用到嘅小工具。',
	'gadgets'           => '小工具',
	'gadgets-title'     => '小工具',
	'gadgets-pagetext'  => '下面係一個按照[[MediaWiki:Gadgets-definition]]嘅定義特別小工具清單，用戶可以響佢哋嘅喜好設定頁度開佢哋。
呢個概覽提供嘅系統信息頁嘅簡易存取，可以定義每個小工具嘅描述同埋碼。',
	'gadgets-uses'      => '用',
);

/** Simplified Chinese (‪中文(简体)‬)
 * @author Shinjiman
 */
$messages['zh-hans'] = array(
	'gadgets-prefs'     => '小工具',
	'gadgets-prefstext' => '以下是一个特殊小工具，您可以在您的账户中激活。
这些小工具多数都是基于JavaScript建造，如果要激活它们，那么浏览器的JavaScript就需要激活后方可使用。
要留意的是这些小工具在这个参数设置页面中是没有效果的。

亦都同时留意这些小工具的特殊页面不是MediaWiki软件的一部份，通常都是由您本地的wiki中开发以及维护。本地管理员可以在[[MediaWiki:Gadgets-definition]]以及[[Special:Gadgets]]编辑可供使用的小工具。',
	'gadgets'           => '小工具',
	'gadgets-title'     => '小工具',
	'gadgets-pagetext'  => '以下是一个按照[[MediaWiki:Gadgets-definition]]的定义特殊小工具列表，用户可以在它们的参数设置页面中激活它们。
这个概览提供的系统信息页面的简易存取，可以定义每个小工具的描述以及源码。',
	'gadgets-uses'      => '使用',
);

/** Traditional Chinese (‪中文(繁體)‬)
 * @author Shinjiman
 */
$messages['zh-hant'] = array(
	'gadgets-prefs'     => '小工具',
	'gadgets-prefstext' => '以下是一個特殊小工具，您可以在您的帳戶中啟用。
這些小工具多數都是基於JavaScript建造，如果要啟用它們，那麼瀏覽器的JavaScript就需要啟用後方可使用。
要留意的是這些小工具在這個參數設置頁面中是沒有效果的。

亦都同時留意這些小工具的特殊頁面不是MediaWiki軟件的一部份，通常都是由您本地的wiki中開發以及維護。本地管理員可以在[[MediaWiki:Gadgets-definition]]以及[[Special:Gadgets]]編輯可供使用的小工具。',
	'gadgets'           => '小工具',
	'gadgets-title'     => '小工具',
	'gadgets-pagetext'  => '以下是一個按照[[MediaWiki:Gadgets-definition]]的定義特殊小工具清單，用戶可以在它們的參數設置頁面中啟用它們。
這個概覽提供的系統信息頁面的簡易存取，可以定義每個小工具的描述以及原碼。',
	'gadgets-uses'      => '使用',
);

