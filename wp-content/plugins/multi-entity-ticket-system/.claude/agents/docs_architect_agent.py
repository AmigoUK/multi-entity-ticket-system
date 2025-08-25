#!/usr/bin/env python3
"""
Docs Architect Agent for Qwen Code
"""

import json
import os
import re
import sys
import traceback
from pathlib import Path
from typing import Dict, List, Optional, Tuple

class DocsArchitectAgent:
    """Expert documentation architect specializing in creating comprehensive, user-focused documentation."""
    
    def __init__(self):
        self.name = "docs-architect"
        self.description = "Documentation architect for creating comprehensive, user-focused documentation"
        self.model = "qwen"
        
    def analyze_code_structure(self, file_path: str) -> Dict:
        """
        Analyze code structure to understand functionality.
        
        Args:
            file_path (str): Path to code file
            
        Returns:
            Dict: Analysis of code structure
        """
        analysis = {
            'classes': [],
            'functions': [],
            'hooks': [],
            'shortcodes': [],
            'constants': [],
            'dependencies': [],
            'file_info': {
                'path': file_path,
                'name': os.path.basename(file_path),
                'extension': os.path.splitext(file_path)[1]
            }
        }
        
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # Analyze classes
            class_pattern = r'class\s+(\w+)'
            classes = re.findall(class_pattern, content)
            analysis['classes'] = classes
            
            # Analyze functions
            function_pattern = r'function\s+(\w+)\s*\('
            functions = re.findall(function_pattern, content)
            analysis['functions'] = functions
            
            # Analyze WordPress hooks
            hook_patterns = [
                r'add_action\s*\(\s*[\'"]([^\'"]+)[\'"]',
                r'add_filter\s*\(\s*[\'"]([^\'"]+)[\'"]',
                r'do_action\s*\(\s*[\'"]([^\'"]+)[\'"]',
                r'apply_filters\s*\(\s*[\'"]([^\'"]+)[\'"]'
            ]
            
            hooks = []
            for pattern in hook_patterns:
                matches = re.findall(pattern, content)
                hooks.extend(matches)
                
            analysis['hooks'] = list(set(hooks))  # Remove duplicates
            
            # Analyze shortcodes
            shortcode_pattern = r'add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]'
            shortcodes = re.findall(shortcode_pattern, content)
            analysis['shortcodes'] = shortcodes
            
            # Analyze constants
            const_pattern = r'define\s*\(\s*[\'"]([^\'"]+)[\'"]'
            constants = re.findall(const_pattern, content)
            analysis['constants'] = constants
            
            # Analyze dependencies
            require_pattern = r'(require_once|require|include_once|include)\s*\(\s*[\'"][^\'"]*([^\']*)[\'"]'
            dependencies = re.findall(require_pattern, content)
            analysis['dependencies'] = [dep[1] for dep in dependencies]
            
        except Exception as e:
            analysis['error'] = str(e)
            analysis['traceback'] = traceback.format_exc()
            
        return analysis
        
    def identify_audience_needs(self, code_analysis: Dict) -> Dict:
        """
        Identify audience needs based on code analysis.
        
        Args:
            code_analysis (Dict): Analysis of code structure
            
        Returns:
            Dict: Audience needs and requirements
        """
        needs = {
            'developers': [],
            'administrators': [],
            'end_users': [],
            'integrators': []
        }
        
        # Analyze based on code features
        if code_analysis.get('shortcodes'):
            needs['end_users'].append('How to use shortcodes in posts/pages')
            needs['developers'].append('Shortcode implementation details')
            
        if code_analysis.get('hooks'):
            needs['developers'].append('Available hooks for customization')
            needs['integrators'].append('Integration points with other plugins')
            
        if code_analysis.get('classes'):
            needs['developers'].append('Class methods and properties')
            
        if code_analysis.get('functions'):
            needs['developers'].append('Function parameters and return values')
            
        return needs
        
    def create_documentation_structure(self, code_analysis: Dict, audience_needs: Dict) -> Dict:
        """
        Create documentation structure based on code analysis and audience needs.
        
        Args:
            code_analysis (Dict): Analysis of code structure
            audience_needs (Dict): Audience needs and requirements
            
        Returns:
            Dict: Documentation structure
        """
        structure = {
            'title': f'Documentation for {os.path.basename(code_analysis["file_info"]["path"])}',
            'sections': [],
            'metadata': {
                'file_path': code_analysis['file_info']['path'],
                'generated_date': self._get_current_timestamp(),
                'audience_needs': audience_needs
            }
        }
        
        # Add overview section
        structure['sections'].append({
            'title': 'Overview',
            'content': self._generate_overview_content(code_analysis),
            'type': 'overview'
        })
        
        # Add installation/setup section if relevant
        if self._needs_installation_section(code_analysis):
            structure['sections'].append({
                'title': 'Installation and Setup',
                'content': self._generate_installation_content(code_analysis),
                'type': 'installation'
            })
        
        # Add shortcode documentation
        if code_analysis.get('shortcodes'):
            structure['sections'].append({
                'title': 'Shortcodes',
                'content': self._generate_shortcode_content(code_analysis),
                'type': 'shortcodes'
            })
            
        # Add hook documentation
        if code_analysis.get('hooks'):
            structure['sections'].append({
                'title': 'Hooks',
                'content': self._generate_hook_content(code_analysis),
                'type': 'hooks'
            })
            
        # Add class documentation
        if code_analysis.get('classes'):
            structure['sections'].append({
                'title': 'Classes',
                'content': self._generate_class_content(code_analysis),
                'type': 'classes'
            })
            
        # Add function documentation
        if code_analysis.get('functions'):
            structure['sections'].append({
                'title': 'Functions',
                'content': self._generate_function_content(code_analysis),
                'type': 'functions'
            })
            
        # Add usage examples
        structure['sections'].append({
                'title': 'Usage Examples',
                'content': self._generate_usage_examples(code_analysis),
                'type': 'examples'
            })
        
        # Add troubleshooting section
        structure['sections'].append({
            'title': 'Troubleshooting',
            'content': self._generate_troubleshooting_content(code_analysis),
            'type': 'troubleshooting'
        })
        
        return structure
        
    def _generate_overview_content(self, code_analysis: Dict) -> str:
        """Generate overview content."""
        content = []
        content.append("## Overview")
        content.append("")
        content.append(f"This document provides comprehensive documentation for `{code_analysis['file_info']['name']}`.")
        content.append("")
        
        if code_analysis.get('classes'):
            content.append("**Classes:**")
            for class_name in code_analysis['classes']:
                content.append(f"- `{class_name}`")
            content.append("")
            
        if code_analysis.get('shortcodes'):
            content.append("**Shortcodes:**")
            for shortcode in code_analysis['shortcodes']:
                content.append(f"- `[{shortcode}]`")
            content.append("")
            
        if code_analysis.get('hooks'):
            content.append("**Hooks:**")
            for hook in code_analysis['hooks']:
                content.append(f"- `{hook}`")
            content.append("")
            
        return "\n".join(content)
        
    def _needs_installation_section(self, code_analysis: Dict) -> bool:
        """Determine if installation section is needed."""
        # Check for common installation indicators
        return any([
            'activate' in ' '.join(code_analysis.get('hooks', [])).lower(),
            'install' in ' '.join(code_analysis.get('functions', [])).lower(),
            'setup' in ' '.join(code_analysis.get('functions', [])).lower()
        ])
        
    def _generate_installation_content(self, code_analysis: Dict) -> str:
        """Generate installation content."""
        content = []
        content.append("## Installation and Setup")
        content.append("")
        content.append("### Prerequisites")
        content.append("- WordPress 5.0 or higher")
        content.append("- PHP 7.4 or higher")
        content.append("")
        content.append("### Installation Steps")
        content.append("1. Download the plugin files")
        content.append("2. Upload to the `/wp-content/plugins/` directory")
        content.append("3. Activate the plugin through the 'Plugins' menu in WordPress")
        content.append("4. Configure settings in the plugin admin panel")
        content.append("")
        content.append("### Initial Configuration")
        content.append("After activation, navigate to the plugin settings page to configure:")
        content.append("- Default settings")
        content.append("- User permissions")
        content.append("- Integration options")
        content.append("")
        return "\n".join(content)
        
    def _generate_shortcode_content(self, code_analysis: Dict) -> str:
        """Generate shortcode documentation."""
        content = []
        content.append("## Shortcodes")
        content.append("")
        content.append("The plugin provides the following shortcodes for easy integration into posts and pages:")
        content.append("")
        
        for shortcode in code_analysis.get('shortcodes', []):
            content.append(f"### `[{shortcode}]`")
            content.append("")
            content.append("**Description:** Brief description of what this shortcode does.")
            content.append("")
            content.append("**Parameters:**")
            content.append("- `parameter` (optional): Description of parameter")
            content.append("")
            content.append("**Example:**")
            content.append("```")
            content.append(f"[{shortcode} parameter=\"value\"]")
            content.append("```")
            content.append("")
            
        return "\n".join(content)
        
    def _generate_hook_content(self, code_analysis: Dict) -> str:
        """Generate hook documentation."""
        content = []
        content.append("## Hooks")
        content.append("")
        content.append("The plugin provides several hooks for customization and integration:")
        content.append("")
        
        for hook in code_analysis.get('hooks', []):
            content.append(f"### `{hook}`")
            content.append("")
            content.append("**Type:** Action/Filter")
            content.append("")
            content.append("**Description:** Brief description of when this hook is fired.")
            content.append("")
            content.append("**Parameters:**")
            content.append("- `$parameter`: Description of parameter")
            content.append("")
            content.append("**Example:**")
            content.append("```php")
            content.append("// Example usage")
            content.append(f"add_action('{hook}', function($parameter) {{")
            content.append("    // Your custom code here")
            content.append("}});")
            content.append("```")
            content.append("")
            
        return "\n".join(content)
        
    def _generate_class_content(self, code_analysis: Dict) -> str:
        """Generate class documentation."""
        content = []
        content.append("## Classes")
        content.append("")
        
        for class_name in code_analysis.get('classes', []):
            content.append(f"### `{class_name}`")
            content.append("")
            content.append("**Description:** Brief description of the class purpose.")
            content.append("")
            content.append("**Methods:**")
            content.append("- `method_name()`: Brief description of method")
            content.append("")
            
        return "\n".join(content)
        
    def _generate_function_content(self, code_analysis: Dict) -> str:
        """Generate function documentation."""
        content = []
        content.append("## Functions")
        content.append("")
        content.append("The plugin provides the following public functions:")
        content.append("")
        
        for function_name in code_analysis.get('functions', []):
            content.append(f"### `{function_name}()`")
            content.append("")
            content.append("**Description:** Brief description of what this function does.")
            content.append("")
            content.append("**Parameters:**")
            content.append("- `$parameter` (type): Description of parameter")
            content.append("")
            content.append("**Return Value:**")
            content.append("- `type`: Description of return value")
            content.append("")
            content.append("**Example:**")
            content.append("```php")
            content.append(f"$result = {function_name}($parameter);")
            content.append("```")
            content.append("")
            
        return "\n".join(content)
        
    def _generate_usage_examples(self, code_analysis: Dict) -> str:
        """Generate usage examples."""
        content = []
        content.append("## Usage Examples")
        content.append("")
        content.append("Here are practical examples of how to use the plugin features:")
        content.append("")
        
        # General usage example
        content.append("### Basic Implementation")
        content.append("")
        content.append("```php")
        content.append("<?php")
        content.append("// Example of basic plugin usage")
        content.append("if (function_exists('plugin_function')) {")
        content.append("    $result = plugin_function($parameter);")
        content.append("    echo $result;")
        content.append("}")
        content.append("?>")
        content.append("```")
        content.append("")
        
        # Shortcode usage if available
        if code_analysis.get('shortcodes'):
            content.append("### Shortcode Usage")
            content.append("")
            content.append("Add the following shortcode to any post or page:")
            content.append("```")
            for shortcode in code_analysis['shortcodes'][:1]:  # Show first shortcode
                content.append(f"[{shortcode} parameter=\"value\"]")
            content.append("```")
            content.append("")
            
        return "\n".join(content)
        
    def _generate_troubleshooting_content(self, code_analysis: Dict) -> str:
        """Generate troubleshooting content."""
        content = []
        content.append("## Troubleshooting")
        content.append("")
        content.append("Common issues and their solutions:")
        content.append("")
        content.append("### Issue: Shortcode not displaying")
        content.append("**Solution:** Ensure the plugin is activated and check for conflicts with other plugins.")
        content.append("")
        content.append("### Issue: Error messages in admin panel")
        content.append("**Solution:** Check PHP error logs and ensure WordPress and PHP versions meet requirements.")
        content.append("")
        content.append("### Issue: Performance problems")
        content.append("**Solution:** Disable unnecessary features and enable caching options.")
        content.append("")
        return "\n".join(content)
        
    def _get_current_timestamp(self) -> str:
        """Get current timestamp."""
        import datetime
        return datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        
    def generate_api_documentation(self, code_analysis: Dict) -> Dict:
        """
        Generate API documentation.
        
        Args:
            code_analysis (Dict): Analysis of code structure
            
        Returns:
            Dict: API documentation
        """
        api_docs = {
            'endpoints': [],
            'models': [],
            'authentication': {},
            'rate_limits': {},
            'errors': {}
        }
        
        # This would be expanded for REST API documentation
        return api_docs
        
    def generate_user_guide(self, code_analysis: Dict) -> Dict:
        """
        Generate user guide.
        
        Args:
            code_analysis (Dict): Analysis of code structure
            
        Returns:
            Dict: User guide
        """
        user_guide = {
            'getting_started': {},
            'basic_usage': {},
            'advanced_features': {},
            'faq': {}
        }
        
        # This would be expanded for detailed user guides
        return user_guide
        
    def generate_configuration_guide(self, code_analysis: Dict) -> Dict:
        """
        Generate configuration guide.
        
        Args:
            code_analysis (Dict): Analysis of code structure
            
        Returns:
            Dict: Configuration guide
        """
        config_guide = {
            'settings': [],
            'options': [],
            'permissions': [],
            'integrations': []
        }
        
        # This would be expanded for detailed configuration documentation
        return config_guide
        
    def create_comprehensive_documentation(self, file_path: str) -> Dict:
        """
        Create comprehensive documentation for a code file.
        
        Args:
            file_path (str): Path to code file
            
        Returns:
            Dict: Complete documentation
        """
        documentation = {
            'success': False,
            'file_path': file_path,
            'analysis': {},
            'structure': {},
            'api_docs': {},
            'user_guide': {},
            'config_guide': {},
            'error': None
        }
        
        try:
            # 1. Analyze code structure
            documentation['analysis'] = self.analyze_code_structure(file_path)
            
            # 2. Identify audience needs
            audience_needs = self.identify_audience_needs(documentation['analysis'])
            
            # 3. Create documentation structure
            documentation['structure'] = self.create_documentation_structure(
                documentation['analysis'], 
                audience_needs
            )
            
            # 4. Generate API documentation
            documentation['api_docs'] = self.generate_api_documentation(documentation['analysis'])
            
            # 5. Generate user guide
            documentation['user_guide'] = self.generate_user_guide(documentation['analysis'])
            
            # 6. Generate configuration guide
            documentation['config_guide'] = self.generate_configuration_guide(documentation['analysis'])
            
            documentation['success'] = True
            
        except Exception as e:
            documentation['error'] = str(e)
            documentation['traceback'] = traceback.format_exc()
            
        return documentation
        
    def format_documentation(self, documentation: Dict) -> str:
        """
        Format documentation into human-readable format.
        
        Args:
            documentation (Dict): Complete documentation
            
        Returns:
            str: Formatted documentation
        """
        if not documentation.get('success', False):
            return f"# Documentation Generation Failed\n\nError: {documentation.get('error', 'Unknown error')}"
            
        sections = documentation.get('structure', {}).get('sections', [])
        
        if not sections:
            return "# Documentation\n\nNo documentation sections found."
            
        formatted_docs = []
        
        for section in sections:
            formatted_docs.append(section.get('content', ''))
            
        return "\n\n".join(formatted_docs)

def main():
    """Main entry point for the docs architect agent."""
    if len(sys.argv) < 2:
        print("Usage: python docs_architect_agent.py <file_path> [output_format]")
        print("Supported formats: markdown (default), html, pdf")
        sys.exit(1)
        
    file_path = sys.argv[1]
    output_format = sys.argv[2] if len(sys.argv) > 2 else 'markdown'
    
    # Check if file exists
    if not os.path.exists(file_path):
        print(f"Error: File '{file_path}' not found.")
        sys.exit(1)
        
    # Create docs architect agent
    architect = DocsArchitectAgent()
    
    # Create comprehensive documentation
    documentation = architect.create_comprehensive_documentation(file_path)
    
    # Format documentation
    formatted_docs = architect.format_documentation(documentation)
    
    # Print to stdout
    print(formatted_docs)
    
    # Save to file
    import time
    timestamp = int(time.time())
    output_file = f"documentation_{timestamp}.{output_format}"
    
    with open(output_file, 'w') as f:
        f.write(formatted_docs)
        
    print(f"\nDocumentation saved to: {output_file}")

if __name__ == "__main__":
    main()