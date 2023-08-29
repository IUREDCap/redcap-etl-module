#-------------------------------------------------------
# Copyright (C) 2023 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

./vendor/bin/behat features/admin-config.feature
./vendor/bin/behat features/admin-help.feature
./vendor/bin/behat features/admin-log.feature
./vendor/bin/behat features/admin-pages.feature
./vendor/bin/behat features/admin-project-access.feature
./vendor/bin/behat features/admin-user-management.feature
./vendor/bin/behat features/admin-user-search.feature
./vendor/bin/behat features/autogen-config.feature
./vendor/bin/behat features/etl-config.feature
./vendor/bin/behat features/etl-workflow.feature

./vendor/bin/behat features/extract-filter.feature
./vendor/bin/behat features/file-dowload-admin.feature
./vendor/bin/behat features/file-dowload-user.feature
# ./vendor/bin/behat features/help.feature
./vendor/bin/behat features/schedule.feature
./vendor/bin/behat features/server-access-level-admin.feature
./vendor/bin/behat features/server-access-level-user.feature
./vendor/bin/behat features/server-config.feature
./vendor/bin/behat features/user-interface.feature
./vendor/bin/behat features/workflow.feature
