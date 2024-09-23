=== InPost PL ===
Contributors: inspirelabs
Tags: inpost, paczkomaty, etykiety, przesyłki
Requires at least: 5.3
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.5.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
InPost PL dla WooCommerce to dedykowana wtyczka do integracji, stworzona z myślą o małych i średnich firmach, które chcą w szybki i wygodny sposób zintegrować się z usługami InPost.

== Description ==

InPost PL dla WooCommerce to dedykowana wtyczka do integracji, stworzona z myślą o małych i średnich firmach, które chcą w szybki i wygodny sposób zintegrować się z usługami InPost.

### Jakie korzyści daje zainstalowanie wtyczki?

* dostęp do wszystkich usług kurierskich i Paczkomatowych InPost wraz z ich opisem i aktualnym brandingiem
* stale aktualizowaną mapę Paczkomatów i PaczkoPunktów
* szybki dostęp do bieżących informacji
* automatyczny proces transferu danych adresata w celu realizacji zamówienia
* usprawnienie edycji danych i generowania etykiet nadawczych, a także podgląd historii wysyłek

Po zainstalowaniu wtyczki należy skonfigurować sposób dostawy InPost oraz ceny poszczególnych usług, dzięki czemu użytkownik sklepu internetowego będzie mógł wybrać odpowiednią metodę dostawy dla swojego zamówienia.

**Pełna dokumentacja oraz instrukcja instalacji wtyczki znajduje się pod adresem:**
[https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/61833233/WooCommerce](https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/61833233/WooCommerce)

**W przypadku pytań/problemów związanych z wtyczką, prosimy o kontakt [przez formularz InPost](https://inpost.pl/formularz-wsparcie)**.


== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png
5. screenshot-5.png


== Changelog ==

= 1.5.2 =
* Fix: message details for error during package createing

= 1.5.1 =
* Feat: print post confirmation
* Fix: html of icons

= 1.5.0 =
* Feat: new setting for order insurance
* Fix: choose qty of parcels for "Przesyłka wielopaczkowa"

= 1.4.9 =
* Fix: compatibility error for Courier parcels
* Fix: change COD amount before creating parcel 

= 1.4.8 =
* Feat: add feature "SMS" and "Email" notifications for Courier methods
* Fix: validation error for block checkout

= 1.4.7 =
* Feat: add feature "Przesyłka wielopaczkowa" for method Courier COD (pobranie)

= 1.4.6 =
* Fix: compatibility with CheckoutWC plugin
* Fix: map params for Checkout blocks checkout

= 1.4.5 =
* Fix: error on order status change
* Feat: add feature "Przesyłka wielopaczkowa" for method Courier standard

= 1.4.4 =
* Fix: integration with Flexible Shipping for feature "Druga paczka"
* Fix: colspan on Checkout page
* Fix: adding buyer's note for order in Bulk creating mode

= 1.4.3 =
* Fix: error in JS for Kurier COD method
* Feat: printing multiple labels for additional packages

= 1.4.2 =
* Fix: validation  error for SmartCourier on checkout

= 1.4.1 =
* Fix: print label format

= 1.4.0 =
* Feat: nowa metoda dostawy "Smart Courier"

= 1.3.9 =
* Fix: błąd kwoty ubezpieczenia

= 1.3.8 =
* Korekta wyświetlania kwoty ubezpieczenia

= 1.3.7 =
* Więcej informacji o adresie punktu
* Inne drobne poprawki

= 1.3.6 =
* Funkcjonalność - utworzenie drugiej paczki

= 1.3.5 =
* Fix saving data to order meta for HPOS for all methods

= 1.3.4 =
* Fix saving data to order meta for HPOS

= 1.3.3 =
* Fix deprecated error, fix saving meta to order details

= 1.3.2 =
* Fix dispatch methods for Courier C2C method and COD amount for C2C COD

= 1.3.1 =
* New setting - default size for Courier C2C method

= 1.3.0 =
* Limit of order total in 5000 for COD methods
* Fix new block's Checkout when only one Inpost shipping method is enabled

= 1.2.9 =
* CSS fix
* Change enquene of "front-blocks.js" script 
* New setting for shipping methods - disable/enable "free shipping" notice 

= 1.2.8 =
* Fix conflict in scripts

= 1.2.7 =
* Integracja z Woo Blocks checkout page
* Additional settings in Debug section

= 1.2.6 =
* Fix validation error if parcel locker data is missed
* Change enquene of "inpost-geowidget.js" script
* Fix deprecated errors for PHP 8.2

= 1.2.5 =
* Fix map opening on plugin setting's page
* Autocomplete field "parcel size" for parcel locker shipments based on settings saved in product

= 1.2.4 =
* Some small fixs

= 1.2.3 =
* Fix integracja z HPOS
* Fix nr. Paczkomatu Divi Theme

= 1.2.2 =
* Integracja z HPOS

= 1.2.1 =
* Naprawiono: integracja z wtyczką Flexible Shipping

= 1.2.0 =
* Zapisywanie danych punktu przy Checkout do LocalStorage
  Naprawiono: podwójna ulica na etykiecie

= 1.1.9 =
* Możliwość zmiany statusu zamówienia w przypadku tworzenia paczki

= 1.1.8 =
* Funkcjonalność - ustawienia domyślnego wymiaru dla przesyłki kurierskiej

= 1.1.7 =
* Funkcjonalność - Kupony na dostawę

= 1.1.6 =
* Nowa metoda - InPost Paczka Ekonomiczna

= 1.1.5 =
* Nowa metoda - InPost Kurier C2C COD
	Nowe ustawienie uwzględniania (lub ignorowania) kuponów na kwotę bezpłatnej wysyłki

= 1.1.4 =
* Obsługa opcji klas wysyłkowych


= 1.1.3 =
* Naprawiono: style CSS
	podwójne ostrzeżenie na stronie kasy,
    podwójna wiadomość w e-mailu w przypadku darmowej wysyłki,
    błąd sposobu obliczania darmowej przesyłki przy użyciu kuponu

= 1.1.2 =
* Naprawiono: błąd w uzyskaniu wagi z danych produktu
	Zmiana sposobu obliczania darmowej przesyłki przy użyciu kuponu
	Zmiana logo

= 1.1.1 =
* Refaktoryzacja połączenia z API
	Zmiana tytułu i treści wiadomości e-mail z numerem przesyłki

= 1.1.0 =
* Naprawiono: błąd wyświetlania listy zamówień

= 1.0.9 =
* Naprawiono: błąd podczas pobierania listy metod wysyłki w niektórych sklepach

= 1.0.8 =
* Masowe tworzenie przesyłek
* Możliwość wyboru koloru przycisku do wywołania karty
* Naprawiono: podłączenie skryptów

= 1.0.7 =
* Integracja z wtyczką Flexible Shipping - usunięto ukrywania metod InPost w ustawieniach Woocommerce
* Naprawiono: kalkulacja ceny na podstawie wymiarów przy usuwaniu towaru z koszyka

= 1.0.6 =
* Integracja z wtyczką Flexible Shipping
* Zmiana funkcjonalności dozwolonych metod dostawy w ustawieniach produktu

= 1.0.5 =
* Naprawiono: stawki dla metod powielanych

= 1.0.4 =
* Dodana usługa 'Paczka w Weekend'.
* Naprawiono: przeładowania strony przy wyborze sposobu wysyłki

= 1.0.3 =
* Naprawiono: błąd walidacji pola na stronie kasy,
    błąd pobierania rozmiaru paczki na stronie szczegółów zamówienia,
    błąd z przyciskami na stronie 'Przesyłki'

= 1.0.2 =
* Zmieniono nazwę rozmiarów paczek w szczegółach zamówienia.

= 1.0.1 =
* naprawiono: linki w sekcji Moje konto.

= 1.0.0 =
* wstępne wydanie.
