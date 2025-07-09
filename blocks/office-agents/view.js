/**
 * Office Agents Block - WordPress Interactivity API
 * 
 * Handles frontend interactivity for agent display, sorting, and pagination
 */

import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'realsatisfied-office-agents', {
	state: {
		get currentAgents() {
			const context = getContext();
			const { agents, currentPage, itemsPerPage, enablePagination } = context;
			
			if (!enablePagination) {
				return agents;
			}
			
			const start = (currentPage - 1) * itemsPerPage;
			const end = start + itemsPerPage;
			return agents.slice(start, end);
		},
		
		get canGoPrev() {
			const context = getContext();
			return context.currentPage > 1;
		},
		
		get canGoNext() {
			const context = getContext();
			return context.currentPage < context.totalPages;
		},
		
		get pageInfo() {
			const context = getContext();
			const { currentPage, totalPages, itemsPerPage } = context;
			const currentAgents = state.currentAgents;
			const totalAgents = context.agents.length;
			
			if (!context.enablePagination) {
				return `Showing ${currentAgents.length} of ${totalAgents} agents`;
			}
			
			const start = (currentPage - 1) * itemsPerPage + 1;
			const end = Math.min(currentPage * itemsPerPage, totalAgents);
			
			return `Page ${currentPage} of ${totalPages} (${start}-${end} of ${totalAgents})`;
		}
	},
	
	actions: {
		prevPage: () => {
			const context = getContext();
			if (context.currentPage > 1) {
				context.currentPage--;
			}
		},
		
		nextPage: () => {
			const context = getContext();
			if (context.currentPage < context.totalPages) {
				context.currentPage++;
			}
		},
		
		sortAgents: (event) => {
			const context = getContext();
			const sortValue = event.target.value;
			
			// Update context sort option
			context.sortBy = sortValue;
			
			// Sort agents based on selection
			const sortedAgents = [...context.agents];
			
			switch (sortValue) {
				case 'name':
					sortedAgents.sort((a, b) => a.display_name.localeCompare(b.display_name));
					break;
				case 'rating':
					sortedAgents.sort((a, b) => {
						const ratingA = parseFloat(a.overall_rating) || 0;
						const ratingB = parseFloat(b.overall_rating) || 0;
						return ratingB - ratingA; // Highest first
					});
					break;
				case 'reviews':
					sortedAgents.sort((a, b) => {
						const reviewsA = parseInt(a.review_count) || 0;
						const reviewsB = parseInt(b.review_count) || 0;
						return reviewsB - reviewsA; // Most reviews first
					});
					break;
			}
			
			// Update agents array and reset to first page
			context.agents = sortedAgents;
			context.currentPage = 1;
		}
	},
	
	callbacks: {
		initAgents: () => {
			const context = getContext();
			
			// Initialize any additional setup if needed
			console.log('Office Agents block initialized with', context.agents.length, 'agents');
			
			// Set up any event listeners or additional initialization
			if (context.layout === 'slider') {
				// Initialize slider if needed
				// This would be where you'd set up FlexSlider or similar
			}
		}
	}
} );
