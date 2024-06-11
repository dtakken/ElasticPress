describe('Set password on post', () => {
	it('Removes Post from Index when Password is Set', () => {
		cy.login();
		cy.maybeDisableFeature('protected_content');

		// Delete previous posts, so we can be sure we just expect 1 post.
		cy.wpCli('post list --format=ids').then((wpCliResponse) => {
			if (wpCliResponse.stdout !== '') {
				cy.wpCli(`post delete ${wpCliResponse.stdout}`);
			}
		});

		cy.publishPost({
			title: 'Protected Post Removal Test',
		});

		/**
		 * Give Elasticsearch some time to process the new post.
		 *
		 * @todo instead of waiting for an arbitrary time, we should ensure the post is stored.
		 */
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait(2000);

		// Post is indexed
		cy.visit('/?s=Protected+Post+Removal+Test');
		cy.contains('.site-content article h2', 'Protected Post Removal Test').should('exist');

		cy.wpCli('post list --format=ids').then((wpCliResponse) => {
			if (wpCliResponse.stdout !== '') {
				cy.postSetPassword(wpCliResponse.stdout, 'enter');
			}
		});

		/**
		 * Give Elasticsearch some time to process the update.
		 *
		 * @todo instead of waiting for an arbitrary time, we should ensure the post is stored.
		 */
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait(2000);

		// Post is removed from index
		cy.visit('/?s=Protected+Post+Removal+Test');
		cy.contains('.site-content article h2', 'Protected Post Removal Test').should('not.exist');
	});
});
