DOMAIN_TEST_RUN=SKIP_TEST_DB_PREPARE=1 ./scripts/run_reservas2_tests.sh

.PHONY: \
	test-db-prepare \
	test-fast \
	test-smoke \
	test-dominios-all \
	test-usuario-unit test-usuario-functional test-usuario-all \
	test-tarifa-unit test-tarifa-functional test-tarifa-all \
	test-cabana-unit test-cabana-functional test-cabana-all \
	test-request-reserva-unit test-request-reserva-functional test-request-reserva-all \
	test-reserva-unit test-reserva-functional test-reserva-all \
	test-reservas-core-all

test-db-prepare:
	./scripts/prepare_reservas2_test_db.sh

test-fast: test-db-prepare
	$(DOMAIN_TEST_RUN) unit \
		tests/unit/models/UsuarioDominioTest.php \
		tests/unit/models/TarifaDominioTest.php \
		tests/unit/models/CabanaDominioTest.php \
		tests/unit/models/RequestReservaDominioTest.php \
		tests/unit/models/ReservaDominioTest.php

test-smoke: test-db-prepare
	$(DOMAIN_TEST_RUN) functional \
		tests/functional/TarifaCest.php \
		tests/functional/CabanaCest.php \
		tests/functional/RequestReservaCest.php \
		tests/functional/ReservaCest.php

test-usuario-unit:
	./scripts/run_reservas2_tests.sh unit tests/unit/models/UsuarioDominioTest.php tests/unit/models/UsuarioSearchDominioTest.php

test-usuario-functional:
	./scripts/run_reservas2_tests.sh functional tests/functional/UsuarioPermisosCest.php tests/functional/UsuarioCrudCest.php

test-usuario-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/UsuarioDominioTest.php tests/unit/models/UsuarioSearchDominioTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/UsuarioPermisosCest.php tests/functional/UsuarioCrudCest.php

test-tarifa-unit:
	./scripts/run_reservas2_tests.sh unit tests/unit/models/TarifaDominioTest.php tests/unit/models/TarifaSearchTest.php

test-tarifa-functional:
	./scripts/run_reservas2_tests.sh functional tests/functional/TarifaCest.php

test-tarifa-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/TarifaDominioTest.php tests/unit/models/TarifaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/TarifaCest.php

test-cabana-unit:
	./scripts/run_reservas2_tests.sh unit tests/unit/models/CabanaDominioTest.php tests/unit/models/CabanaSearchTest.php

test-cabana-functional:
	./scripts/run_reservas2_tests.sh functional tests/functional/CabanaCest.php

test-cabana-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/CabanaDominioTest.php tests/unit/models/CabanaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/CabanaCest.php

test-request-reserva-unit:
	./scripts/run_reservas2_tests.sh unit tests/unit/models/RequestReservaDominioTest.php tests/unit/models/RequestReservaSearchTest.php

test-request-reserva-functional:
	./scripts/run_reservas2_tests.sh functional tests/functional/RequestReservaCest.php

test-request-reserva-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/RequestReservaDominioTest.php tests/unit/models/RequestReservaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/RequestReservaCest.php

test-reserva-unit:
	./scripts/run_reservas2_tests.sh unit tests/unit/models/ReservaDominioTest.php tests/unit/models/ReservaSearchTest.php

test-reserva-functional:
	./scripts/run_reservas2_tests.sh functional tests/functional/ReservaCest.php

test-reserva-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/ReservaDominioTest.php tests/unit/models/ReservaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/ReservaCest.php

test-reservas-core-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/TarifaDominioTest.php tests/unit/models/TarifaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/CabanaDominioTest.php tests/unit/models/CabanaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/RequestReservaDominioTest.php tests/unit/models/RequestReservaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/ReservaDominioTest.php tests/unit/models/ReservaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/TarifaCest.php tests/functional/CabanaCest.php tests/functional/RequestReservaCest.php tests/functional/ReservaCest.php

test-dominios-all: test-db-prepare
	$(DOMAIN_TEST_RUN) unit tests/unit/models/UsuarioDominioTest.php tests/unit/models/UsuarioSearchDominioTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/TarifaDominioTest.php tests/unit/models/TarifaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/CabanaDominioTest.php tests/unit/models/CabanaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/RequestReservaDominioTest.php tests/unit/models/RequestReservaSearchTest.php
	$(DOMAIN_TEST_RUN) unit tests/unit/models/ReservaDominioTest.php tests/unit/models/ReservaSearchTest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/UsuarioPermisosCest.php tests/functional/UsuarioCrudCest.php
	$(DOMAIN_TEST_RUN) functional tests/functional/TarifaCest.php tests/functional/CabanaCest.php tests/functional/RequestReservaCest.php tests/functional/ReservaCest.php
