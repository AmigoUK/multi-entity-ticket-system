#!/usr/bin/env python3
"""
DX Optimizer Agent for Qwen Code
"""

import json
import os
import re
import subprocess
import sys
import traceback
from pathlib import Path
from typing import Dict, List, Optional, Tuple

class DXOptimizerAgent:
    """Expert developer experience optimizer specializing in workflow improvement and tooling enhancement."""
    
    def __init__(self):
        self.name = "dx-optimizer"
        self.description = "Developer experience optimizer for improving workflow, tooling, and overall development experience"
        self.model = "qwen"
        
    def analyze_development_environment(self, project_root: str) -> Dict:
        """
        Analyze development environment to identify friction points.
        
        Args:
            project_root (str): Project root directory
            
        Returns:
            Dict: Analysis of development environment
        """
        analysis = {
            'environment': {},
            'tooling': {},
            'workflows': {},
            'pain_points': [],
            'recommendations': []
        }
        
        try:
            # Analyze environment
            analysis['environment'] = self._analyze_environment(project_root)
            
            # Analyze tooling
            analysis['tooling'] = self._analyze_tooling(project_root)
            
            # Analyze workflows
            analysis['workflows'] = self._analyze_workflows(project_root)
            
            # Identify pain points
            analysis['pain_points'] = self._identify_pain_points(analysis)
            
            # Generate recommendations
            analysis['recommendations'] = self._generate_recommendations(analysis)
            
        except Exception as e:
            analysis['error'] = str(e)
            analysis['traceback'] = traceback.format_exc()
            
        return analysis
        
    def _analyze_environment(self, project_root: str) -> Dict:
        """Analyze development environment."""
        environment = {
            'os': self._get_os_info(),
            'php_version': self._get_php_version(),
            'database_info': self._get_database_info(project_root),
            'web_server': self._get_web_server_info()
        }
        return environment
        
    def _analyze_tooling(self, project_root: str) -> Dict:
        """Analyze development tooling."""
        tooling = {
            'version_control': self._analyze_version_control(project_root),
            'package_managers': self._analyze_package_managers(project_root),
            'testing_frameworks': self._analyze_testing_frameworks(project_root),
            'linters': self._analyze_linters(project_root),
            'debuggers': self._analyze_debuggers(project_root),
            'ci_cd': self._analyze_ci_cd(project_root)
        }
        return tooling
        
    def _analyze_workflows(self, project_root: str) -> Dict:
        """Analyze development workflows."""
        workflows = {
            'setup_process': self._analyze_setup_process(project_root),
            'development_cycle': self._analyze_development_cycle(project_root),
            'testing_process': self._analyze_testing_process(project_root),
            'deployment_process': self._analyze_deployment_process(project_root),
            'collaboration_tools': self._analyze_collaboration_tools(project_root)
        }
        return workflows
        
    def _identify_pain_points(self, analysis: Dict) -> List[Dict]:
        """Identify pain points from analysis."""
        pain_points = []
        
        # Environment pain points
        if not analysis['environment']['php_version']:
            pain_points.append({
                'category': 'environment',
                'severity': 'high',
                'description': 'PHP version not detected',
                'impact': 'Cannot verify compatibility',
                'solution': 'Install and configure PHP'
            })
            
        # Tooling pain points
        if not analysis['tooling']['version_control']['initialized']:
            pain_points.append({
                'category': 'tooling',
                'severity': 'high',
                'description': 'Version control not initialized',
                'impact': 'No history tracking or collaboration',
                'solution': 'Initialize Git repository'
            })
            
        # Workflow pain points
        if not analysis['workflows']['setup_process']['automated']:
            pain_points.append({
                'category': 'workflow',
                'severity': 'medium',
                'description': 'Manual setup process',
                'impact': 'Slow onboarding and environment setup',
                'solution': 'Create automated setup scripts'
            })
            
        return pain_points
        
    def _generate_recommendations(self, analysis: Dict) -> List[Dict]:
        """Generate recommendations from analysis."""
        recommendations = []
        
        # Environment recommendations
        if not analysis['environment']['php_version']:
            recommendations.append({
                'category': 'environment',
                'priority': 'high',
                'description': 'Install PHP with proper version',
                'benefits': ['Version consistency', 'Compatibility verification'],
                'effort': 'low',
                'implementation': 'Install PHP 8.0+ and configure PATH'
            })
            
        # Tooling recommendations
        if not analysis['tooling']['version_control']['initialized']:
            recommendations.append({
                'category': 'tooling',
                'priority': 'high',
                'description': 'Initialize Git repository',
                'benefits': ['History tracking', 'Collaboration', 'Branching'],
                'effort': 'low',
                'implementation': 'git init && git add . && git commit -m "Initial commit"'
            })
            
        # Workflow recommendations
        if not analysis['workflows']['setup_process']['automated']:
            recommendations.append({
                'category': 'workflow',
                'priority': 'medium',
                'description': 'Create automated setup script',
                'benefits': ['Faster onboarding', 'Consistent environments'],
                'effort': 'medium',
                'implementation': 'Create setup.sh or setup.bat script'
            })
            
        return recommendations
        
    def _get_os_info(self) -> Dict:
        """Get operating system information."""
        import platform
        return {
            'name': platform.system(),
            'version': platform.release(),
            'architecture': platform.machine()
        }
        
    def _get_php_version(self) -> Optional[str]:
        """Get PHP version."""
        try:
            result = subprocess.run(['php', '--version'], capture_output=True, text=True, timeout=10)
            if result.returncode == 0:
                version_line = result.stdout.split('\n')[0]
                version_match = re.search(r'PHP (\d+\.\d+\.\d+)', version_line)
                if version_match:
                    return version_match.group(1)
        except Exception:
            pass
        return None
        
    def _get_database_info(self, project_root: str) -> Dict:
        """Get database information."""
        database_info = {
            'type': 'unknown',
            'version': 'unknown',
            'host': 'localhost',
            'port': 3306
        }
        
        # Check for wp-config.php
        wp_config_path = os.path.join(project_root, 'wp-config.php')
        if os.path.exists(wp_config_path):
            try:
                with open(wp_config_path, 'r') as f:
                    content = f.read()
                    
                # Extract database info
                db_name_match = re.search(r"define\(\s*'DB_NAME',\s*'([^']+)'", content)
                db_user_match = re.search(r"define\(\s*'DB_USER',\s*'([^']+)'", content)
                db_host_match = re.search(r"define\(\s*'DB_HOST',\s*'([^']+)'", content)
                
                if db_name_match:
                    database_info['name'] = db_name_match.group(1)
                if db_user_match:
                    database_info['user'] = db_user_match.group(1)
                if db_host_match:
                    database_info['host'] = db_host_match.group(1)
                    
                database_info['type'] = 'mysql'
            except Exception:
                pass
                
        return database_info
        
    def _get_web_server_info(self) -> Dict:
        """Get web server information."""
        web_server = {
            'type': 'unknown',
            'version': 'unknown'
        }
        
        try:
            # Check for common web servers
            apache_result = subprocess.run(['apache2', '-v'], capture_output=True, text=True, timeout=5)
            if apache_result.returncode == 0:
                web_server['type'] = 'apache'
                version_match = re.search(r'Apache/(\d+\.\d+\.\d+)', apache_result.stdout)
                if version_match:
                    web_server['version'] = version_match.group(1)
                    
            nginx_result = subprocess.run(['nginx', '-v'], capture_output=True, text=True, timeout=5)
            if nginx_result.returncode == 0:
                web_server['type'] = 'nginx'
                version_match = re.search(r'nginx/(\d+\.\d+\.\d+)', nginx_result.stderr)
                if version_match:
                    web_server['version'] = version_match.group(1)
        except Exception:
            pass
            
        return web_server
        
    def _analyze_version_control(self, project_root: str) -> Dict:
        """Analyze version control system."""
        vc_info = {
            'type': 'none',
            'initialized': False,
            'branch': 'unknown',
            'last_commit': 'unknown'
        }
        
        # Check for Git
        git_dir = os.path.join(project_root, '.git')
        if os.path.exists(git_dir):
            vc_info['type'] = 'git'
            vc_info['initialized'] = True
            
            try:
                # Get current branch
                branch_result = subprocess.run(['git', 'branch', '--show-current'], 
                                             cwd=project_root, capture_output=True, text=True, timeout=5)
                if branch_result.returncode == 0:
                    vc_info['branch'] = branch_result.stdout.strip()
                    
                # Get last commit
                commit_result = subprocess.run(['git', 'log', '-1', '--format=%h - %an, %ar : %s'], 
                                             cwd=project_root, capture_output=True, text=True, timeout=5)
                if commit_result.returncode == 0:
                    vc_info['last_commit'] = commit_result.stdout.strip()
            except Exception:
                pass
                
        return vc_info
        
    def _analyze_package_managers(self, project_root: str) -> Dict:
        """Analyze package managers."""
        pm_info = {
            'composer': False,
            'npm': False,
            'yarn': False
        }
        
        # Check for composer.json
        composer_json = os.path.join(project_root, 'composer.json')
        if os.path.exists(composer_json):
            pm_info['composer'] = True
            
        # Check for package.json
        package_json = os.path.join(project_root, 'package.json')
        if os.path.exists(package_json):
            pm_info['npm'] = True
            
        # Check for yarn.lock
        yarn_lock = os.path.join(project_root, 'yarn.lock')
        if os.path.exists(yarn_lock):
            pm_info['yarn'] = True
            
        return pm_info
        
    def _analyze_testing_frameworks(self, project_root: str) -> Dict:
        """Analyze testing frameworks."""
        tf_info = {
            'phpunit': False,
            'wpunit': False,
            'jest': False,
            'mocha': False
        }
        
        # Check for PHPUnit configuration
        phpunit_xml = os.path.join(project_root, 'phpunit.xml')
        phpunit_dist = os.path.join(project_root, 'phpunit.xml.dist')
        if os.path.exists(phpunit_xml) or os.path.exists(phpunit_dist):
            tf_info['phpunit'] = True
            
        # Check for WP-Unit
        wp_tests_dir = os.path.join(project_root, 'tests')
        if os.path.exists(wp_tests_dir):
            tf_info['wpunit'] = True
            
        # Check for JavaScript testing
        package_json = os.path.join(project_root, 'package.json')
        if os.path.exists(package_json):
            try:
                with open(package_json, 'r') as f:
                    content = json.load(f)
                    
                dev_deps = content.get('devDependencies', {})
                scripts = content.get('scripts', {})
                
                if 'jest' in dev_deps or 'test' in scripts and 'jest' in scripts['test']:
                    tf_info['jest'] = True
                    
                if 'mocha' in dev_deps or 'test' in scripts and 'mocha' in scripts['test']:
                    tf_info['mocha'] = True
            except Exception:
                pass
                
        return tf_info
        
    def _analyze_linters(self, project_root: str) -> Dict:
        """Analyze linters."""
        linter_info = {
            'phpcs': False,
            'eslint': False,
            'stylelint': False,
            'prettier': False
        }
        
        # Check for PHPCS configuration
        phpcs_xml = os.path.join(project_root, 'phpcs.xml')
        phpcs_dist = os.path.join(project_root, 'phpcs.xml.dist')
        if os.path.exists(phpcs_xml) or os.path.exists(phpcs_dist):
            linter_info['phpcs'] = True
            
        # Check for ESLint configuration
        eslint_rc = os.path.join(project_root, '.eslintrc')
        eslint_json = os.path.join(project_root, '.eslintrc.json')
        if os.path.exists(eslint_rc) or os.path.exists(eslint_json):
            linter_info['eslint'] = True
            
        # Check for Stylelint configuration
        stylelint_rc = os.path.join(project_root, '.stylelintrc')
        stylelint_json = os.path.join(project_root, '.stylelintrc.json')
        if os.path.exists(stylelint_rc) or os.path.exists(stylelint_json):
            linter_info['stylelint'] = True
            
        # Check for Prettier configuration
        prettier_rc = os.path.join(project_root, '.prettierrc')
        prettier_json = os.path.join(project_root, '.prettierrc.json')
        if os.path.exists(prettier_rc) or os.path.exists(prettier_json):
            linter_info['prettier'] = True
            
        return linter_info
        
    def _analyze_debuggers(self, project_root: str) -> Dict:
        """Analyze debuggers."""
        debugger_info = {
            'xdebug': False,
            'chrome_devtools': False,
            'firefox_devtools': False
        }
        
        # Check for Xdebug (requires PHP to be running)
        try:
            php_info_result = subprocess.run(['php', '-m'], capture_output=True, text=True, timeout=10)
            if php_info_result.returncode == 0 and 'xdebug' in php_info_result.stdout.lower():
                debugger_info['xdebug'] = True
        except Exception:
            pass
            
        # Check for browser devtools (can't detect directly, but note common browsers)
        debugger_info['chrome_devtools'] = True  # Assume available
        debugger_info['firefox_devtools'] = True  # Assume available
        
        return debugger_info
        
    def _analyze_ci_cd(self, project_root: str) -> Dict:
        """Analyze CI/CD systems."""
        ci_cd_info = {
            'github_actions': False,
            'gitlab_ci': False,
            'travis_ci': False,
            'jenkins': False,
            'circle_ci': False
        }
        
        # Check for GitHub Actions
        github_workflows = os.path.join(project_root, '.github', 'workflows')
        if os.path.exists(github_workflows):
            ci_cd_info['github_actions'] = True
            
        # Check for GitLab CI
        gitlab_ci = os.path.join(project_root, '.gitlab-ci.yml')
        if os.path.exists(gitlab_ci):
            ci_cd_info['gitlab_ci'] = True
            
        # Check for Travis CI
        travis_yml = os.path.join(project_root, '.travis.yml')
        if os.path.exists(travis_yml):
            ci_cd_info['travis_ci'] = True
            
        # Check for Jenkins
        jenkins_file = os.path.join(project_root, 'Jenkinsfile')
        if os.path.exists(jenkins_file):
            ci_cd_info['jenkins'] = True
            
        # Check for CircleCI
        circle_config = os.path.join(project_root, '.circleci', 'config.yml')
        if os.path.exists(circle_config):
            ci_cd_info['circle_ci'] = True
            
        return ci_cd_info
        
    def _analyze_setup_process(self, project_root: str) -> Dict:
        """Analyze setup process."""
        setup_info = {
            'automated': False,
            'documentation': False,
            'dependencies': False,
            'database': False
        }
        
        # Check for setup scripts
        setup_scripts = [
            os.path.join(project_root, 'setup.sh'),
            os.path.join(project_root, 'setup.bat'),
            os.path.join(project_root, 'install.sh'),
            os.path.join(project_root, 'install.bat'),
            os.path.join(project_root, 'bootstrap.sh')
        ]
        
        for script in setup_scripts:
            if os.path.exists(script):
                setup_info['automated'] = True
                break
                
        # Check for setup documentation
        docs_files = [
            os.path.join(project_root, 'README.md'),
            os.path.join(project_root, 'INSTALL.md'),
            os.path.join(project_root, 'SETUP.md'),
            os.path.join(project_root, 'docs', 'setup.md')
        ]
        
        for doc in docs_files:
            if os.path.exists(doc):
                setup_info['documentation'] = True
                break
                
        # Check for dependency management
        deps_files = [
            os.path.join(project_root, 'composer.json'),
            os.path.join(project_root, 'package.json'),
            os.path.join(project_root, 'requirements.txt')
        ]
        
        for dep in deps_files:
            if os.path.exists(dep):
                setup_info['dependencies'] = True
                break
                
        # Check for database setup
        db_files = [
            os.path.join(project_root, 'database.sql'),
            os.path.join(project_root, 'schema.sql'),
            os.path.join(project_root, 'migrations'),
            os.path.join(project_root, 'db')
        ]
        
        for db_file in db_files:
            if os.path.exists(db_file):
                setup_info['database'] = True
                break
                
        return setup_info
        
    def _analyze_development_cycle(self, project_root: str) -> Dict:
        """Analyze development cycle."""
        cycle_info = {
            'edit': True,  # Always assumed
            'build': False,
            'test': False,
            'deploy': False,
            'monitor': False
        }
        
        # Check for build process
        package_json = os.path.join(project_root, 'package.json')
        if os.path.exists(package_json):
            try:
                with open(package_json, 'r') as f:
                    content = json.load(f)
                    
                scripts = content.get('scripts', {})
                if 'build' in scripts or 'compile' in scripts:
                    cycle_info['build'] = True
            except Exception:
                pass
                
        # Check for testing
        test_dirs = [
            os.path.join(project_root, 'tests'),
            os.path.join(project_root, 'spec'),
            os.path.join(project_root, 'test')
        ]
        
        for test_dir in test_dirs:
            if os.path.exists(test_dir):
                cycle_info['test'] = True
                break
                
        # Check for deployment scripts
        deploy_scripts = [
            os.path.join(project_root, 'deploy.sh'),
            os.path.join(project_root, 'release.sh'),
            os.path.join(project_root, 'publish.sh')
        ]
        
        for script in deploy_scripts:
            if os.path.exists(script):
                cycle_info['deploy'] = True
                break
                
        # Check for monitoring
        monitor_files = [
            os.path.join(project_root, 'monitor.sh'),
            os.path.join(project_root, 'healthcheck.sh'),
            os.path.join(project_root, 'ping.sh')
        ]
        
        for monitor_file in monitor_files:
            if os.path.exists(monitor_file):
                cycle_info['monitor'] = True
                break
                
        return cycle_info
        
    def _analyze_testing_process(self, project_root: str) -> Dict:
        """Analyze testing process."""
        testing_info = {
            'unit_tests': False,
            'integration_tests': False,
            'e2e_tests': False,
            'automated': False,
            'coverage': False
        }
        
        # Check for unit tests
        unit_test_dirs = [
            os.path.join(project_root, 'tests', 'unit'),
            os.path.join(project_root, 'spec', 'unit'),
            os.path.join(project_root, 'test', 'unit')
        ]
        
        for test_dir in unit_test_dirs:
            if os.path.exists(test_dir):
                testing_info['unit_tests'] = True
                break
                
        # Check for integration tests
        integration_test_dirs = [
            os.path.join(project_root, 'tests', 'integration'),
            os.path.join(project_root, 'spec', 'integration'),
            os.path.join(project_root, 'test', 'integration')
        ]
        
        for test_dir in integration_test_dirs:
            if os.path.exists(test_dir):
                testing_info['integration_tests'] = True
                break
                
        # Check for E2E tests
        e2e_test_dirs = [
            os.path.join(project_root, 'tests', 'e2e'),
            os.path.join(project_root, 'spec', 'e2e'),
            os.path.join(project_root, 'test', 'e2e'),
            os.path.join(project_root, 'cypress'),
            os.path.join(project_root, 'playwright')
        ]
        
        for test_dir in e2e_test_dirs:
            if os.path.exists(test_dir):
                testing_info['e2e_tests'] = True
                break
                
        # Check for automated testing
        ci_configs = [
            os.path.join(project_root, '.github', 'workflows'),
            os.path.join(project_root, '.gitlab-ci.yml'),
            os.path.join(project_root, '.travis.yml')
        ]
        
        for ci_config in ci_configs:
            if os.path.exists(ci_config):
                testing_info['automated'] = True
                break
                
        # Check for coverage
        coverage_configs = [
            os.path.join(project_root, 'phpunit.xml'),
            os.path.join(project_root, 'jest.config.js'),
            os.path.join(project_root, '.nycrc')
        ]
        
        for config in coverage_configs:
            if os.path.exists(config):
                testing_info['coverage'] = True
                break
                
        return testing_info
        
    def _analyze_deployment_process(self, project_root: str) -> Dict:
        """Analyze deployment process."""
        deployment_info = {
            'automated': False,
            'staging': False,
            'rollback': False,
            'zero_downtime': False,
            'monitoring': False
        }
        
        # Check for automated deployment
        deploy_scripts = [
            os.path.join(project_root, 'deploy.sh'),
            os.path.join(project_root, 'release.sh'),
            os.path.join(project_root, 'publish.sh')
        ]
        
        for script in deploy_scripts:
            if os.path.exists(script):
                deployment_info['automated'] = True
                break
                
        # Check for staging deployment
        staging_configs = [
            os.path.join(project_root, 'staging.env'),
            os.path.join(project_root, 'environments', 'staging'),
            os.path.join(project_root, 'config', 'staging')
        ]
        
        for config in staging_configs:
            if os.path.exists(config):
                deployment_info['staging'] = True
                break
                
        # Check for rollback capability
        rollback_scripts = [
            os.path.join(project_root, 'rollback.sh'),
            os.path.join(project_root, 'revert.sh')
        ]
        
        for script in rollback_scripts:
            if os.path.exists(script):
                deployment_info['rollback'] = True
                break
                
        # Check for zero downtime deployment
        zero_downtime_indicators = [
            os.path.join(project_root, 'docker-compose.yml'),
            os.path.join(project_root, 'kubernetes'),
            os.path.join(project_root, 'helm')
        ]
        
        for indicator in zero_downtime_indicators:
            if os.path.exists(indicator):
                deployment_info['zero_downtime'] = True
                break
                
        # Check for monitoring
        monitor_scripts = [
            os.path.join(project_root, 'monitor.sh'),
            os.path.join(project_root, 'healthcheck.sh'),
            os.path.join(project_root, 'ping.sh')
        ]
        
        for script in monitor_scripts:
            if os.path.exists(script):
                deployment_info['monitoring'] = True
                break
                
        return deployment_info
        
    def _analyze_collaboration_tools(self, project_root: str) -> Dict:
        """Analyze collaboration tools."""
        collaboration_info = {
            'code_reviews': False,
            'documentation': False,
            'communication': False,
            'project_management': False,
            'knowledge_sharing': False
        }
        
        # Check for code review process
        pr_templates = [
            os.path.join(project_root, '.github', 'pull_request_template.md'),
            os.path.join(project_root, 'PULL_REQUEST_TEMPLATE.md')
        ]
        
        for template in pr_templates:
            if os.path.exists(template):
                collaboration_info['code_reviews'] = True
                break
                
        # Check for documentation
        docs_dirs = [
            os.path.join(project_root, 'docs'),
            os.path.join(project_root, 'wiki'),
            os.path.join(project_root, 'documentation')
        ]
        
        for docs_dir in docs_dirs:
            if os.path.exists(docs_dir):
                collaboration_info['documentation'] = True
                break
                
        # Check for communication tools (can't detect directly, but note common integrations)
        collaboration_info['communication'] = True  # Assume available
        
        # Check for project management (can't detect directly, but note common integrations)
        collaboration_info['project_management'] = True  # Assume available
        
        # Check for knowledge sharing
        knowledge_files = [
            os.path.join(project_root, 'CONTRIBUTING.md'),
            os.path.join(project_root, 'STYLEGUIDE.md'),
            os.path.join(project_root, 'CODE_OF_CONDUCT.md')
        ]
        
        for file in knowledge_files:
            if os.path.exists(file):
                collaboration_info['knowledge_sharing'] = True
                break
                
        return collaboration_info
        
    def optimize_workflow(self, project_root: str) -> Dict:
        """
        Optimize development workflow based on analysis.
        
        Args:
            project_root (str): Project root directory
            
        Returns:
            Dict: Workflow optimization recommendations
        """
        optimization = {
            'success': False,
            'analysis': {},
            'recommendations': [],
            'implementation_plan': [],
            'estimated_effort': {},
            'error': None
        }
        
        try:
            # 1. Analyze current environment
            optimization['analysis'] = self.analyze_development_environment(project_root)
            
            # 2. Generate recommendations
            optimization['recommendations'] = optimization['analysis']['recommendations']
            
            # 3. Create implementation plan
            optimization['implementation_plan'] = self._create_implementation_plan(optimization['recommendations'])
            
            # 4. Estimate effort
            optimization['estimated_effort'] = self._estimate_effort(optimization['recommendations'])
            
            optimization['success'] = True
            
        except Exception as e:
            optimization['error'] = str(e)
            optimization['traceback'] = traceback.format_exc()
            
        return optimization
        
    def _create_implementation_plan(self, recommendations: List[Dict]) -> List[Dict]:
        """Create implementation plan from recommendations."""
        plan = []
        
        # Group recommendations by priority
        high_priority = [r for r in recommendations if r.get('priority') == 'high']
        medium_priority = [r for r in recommendations if r.get('priority') == 'medium']
        low_priority = [r for r in recommendations if r.get('priority') == 'low']
        
        # Add high priority items first
        for rec in high_priority:
            plan.append({
                'step': len(plan) + 1,
                'task': rec['description'],
                'priority': rec['priority'],
                'category': rec['category'],
                'implementation': rec['implementation'],
                'estimated_time': '1-2 hours'
            })
            
        # Add medium priority items
        for rec in medium_priority:
            plan.append({
                'step': len(plan) + 1,
                'task': rec['description'],
                'priority': rec['priority'],
                'category': rec['category'],
                'implementation': rec['implementation'],
                'estimated_time': '2-4 hours'
            })
            
        # Add low priority items
        for rec in low_priority:
            plan.append({
                'step': len(plan) + 1,
                'task': rec['description'],
                'priority': rec['priority'],
                'category': rec['category'],
                'implementation': rec['implementation'],
                'estimated_time': '4-8 hours'
            })
            
        return plan
        
    def _estimate_effort(self, recommendations: List[Dict]) -> Dict:
        """Estimate effort for implementing recommendations."""
        effort = {
            'total_hours': 0,
            'by_category': {},
            'by_priority': {},
            'team_size': 1
        }
        
        # Calculate total hours
        for rec in recommendations:
            priority = rec.get('priority', 'medium')
            category = rec.get('category', 'general')
            
            # Estimate hours based on priority
            hours = 0
            if priority == 'high':
                hours = 2  # 1-2 hours average
            elif priority == 'medium':
                hours = 3  # 2-4 hours average
            elif priority == 'low':
                hours = 6  # 4-8 hours average
                
            effort['total_hours'] += hours
            
            # Track by category
            if category not in effort['by_category']:
                effort['by_category'][category] = 0
            effort['by_category'][category] += hours
            
            # Track by priority
            if priority not in effort['by_priority']:
                effort['by_priority'][priority] = 0
            effort['by_priority'][priority] += hours
            
        return effort
        
    def generate_optimization_report(self, optimization: Dict) -> str:
        """
        Generate optimization report.
        
        Args:
            optimization (Dict): Complete optimization analysis
            
        Returns:
            str: Formatted optimization report
        """
        if not optimization.get('success', False):
            return f"# DX Optimization Failed\n\nError: {optimization.get('error', 'Unknown error')}"
            
        report = []
        report.append("# Developer Experience Optimization Report")
        report.append("")
        
        # Analysis Summary
        analysis = optimization.get('analysis', {})
        report.append("## Analysis Summary")
        report.append("")
        
        # Environment
        environment = analysis.get('environment', {})
        report.append("### Environment")
        report.append(f"- **OS**: {environment.get('os', {}).get('name', 'Unknown')} {environment.get('os', {}).get('version', '')}")
        report.append(f"- **PHP Version**: {environment.get('php_version', 'Not detected')}")
        report.append(f"- **Database**: {environment.get('database_info', {}).get('type', 'Unknown')} {environment.get('database_info', {}).get('version', '')}")
        report.append(f"- **Web Server**: {environment.get('web_server', {}).get('type', 'Unknown')} {environment.get('web_server', {}).get('version', '')}")
        report.append("")
        
        # Pain Points
        pain_points = analysis.get('pain_points', [])
        if pain_points:
            report.append("## Identified Pain Points")
            report.append("")
            for point in pain_points:
                report.append(f"### {point['description']}")
                report.append(f"- **Severity**: {point['severity'].upper()}")
                report.append(f"- **Impact**: {point['impact']}")
                report.append(f"- **Solution**: {point['solution']}")
                report.append("")
                
        # Recommendations
        recommendations = optimization.get('recommendations', [])
        if recommendations:
            report.append("## Optimization Recommendations")
            report.append("")
            
            # Group by priority
            high_priority = [r for r in recommendations if r.get('priority') == 'high']
            medium_priority = [r for r in recommendations if r.get('priority') == 'medium']
            low_priority = [r for r in recommendations if r.get('priority') == 'low']
            
            if high_priority:
                report.append("### High Priority (Implement First)")
                report.append("")
                for rec in high_priority:
                    report.append(f"#### {rec['description']}")
                    report.append(f"- **Category**: {rec['category']}")
                    report.append(f"- **Benefits**: {', '.join(rec.get('benefits', []))}")
                    report.append(f"- **Effort**: {rec.get('effort', 'unknown')}")
                    report.append(f"- **Implementation**: {rec['implementation']}")
                    report.append("")
                    
            if medium_priority:
                report.append("### Medium Priority (Implement Next)")
                report.append("")
                for rec in medium_priority:
                    report.append(f"#### {rec['description']}")
                    report.append(f"- **Category**: {rec['category']}")
                    report.append(f"- **Benefits**: {', '.join(rec.get('benefits', []))}")
                    report.append(f"- **Effort**: {rec.get('effort', 'unknown')}")
                    report.append(f"- **Implementation**: {rec['implementation']}")
                    report.append("")
                    
            if low_priority:
                report.append("### Low Priority (Implement Later)")
                report.append("")
                for rec in low_priority:
                    report.append(f"#### {rec['description']}")
                    report.append(f"- **Category**: {rec['category']}")
                    report.append(f"- **Benefits**: {', '.join(rec.get('benefits', []))}")
                    report.append(f"- **Effort**: {rec.get('effort', 'unknown')}")
                    report.append(f"- **Implementation**: {rec['implementation']}")
                    report.append("")
                    
        # Implementation Plan
        implementation_plan = optimization.get('implementation_plan', [])
        if implementation_plan:
            report.append("## Implementation Plan")
            report.append("")
            report.append("| Step | Task | Priority | Category | Estimated Time |")
            report.append("|------|------|----------|----------|----------------|")
            
            for item in implementation_plan:
                report.append(f"| {item['step']} | {item['task']} | {item['priority']} | {item['category']} | {item['estimated_time']} |")
            report.append("")
            
        # Effort Estimation
        estimated_effort = optimization.get('estimated_effort', {})
        if estimated_effort:
            report.append("## Effort Estimation")
            report.append("")
            report.append(f"- **Total Hours**: {estimated_effort.get('total_hours', 0)} hours")
            report.append(f"- **Team Size**: {estimated_effort.get('team_size', 1)} developer(s)")
            report.append("")
            
            # By category
            by_category = estimated_effort.get('by_category', {})
            if by_category:
                report.append("### By Category")
                for category, hours in by_category.items():
                    report.append(f"- {category.capitalize()}: {hours} hours")
                report.append("")
                
            # By priority
            by_priority = estimated_effort.get('by_priority', {})
            if by_priority:
                report.append("### By Priority")
                for priority, hours in by_priority.items():
                    report.append(f"- {priority.capitalize()}: {hours} hours")
                report.append("")
                
        return "\n".join(report)

def main():
    """Main entry point for the DX optimizer agent."""
    if len(sys.argv) < 2:
        print("Usage: python dx_optimizer_agent.py <error_output_file> [project_root]")
        print("Example: python dx_optimizer_agent.py error.log")
        sys.exit(1)
        
    error_file = sys.argv[1]
    project_root = sys.argv[2] if len(sys.argv) > 2 else '.'
    
    # Check if error file exists
    if not os.path.exists(error_file):
        print(f"Error: Error file '{error_file}' not found.")
        sys.exit(1)
        
    # Read error output
    try:
        with open(error_file, 'r') as f:
            error_output = f.read()
    except Exception as e:
        print(f"Error reading error file: {e}")
        sys.exit(1)
        
    # Create DX optimizer agent
    optimizer = DXOptimizerAgent()
    
    # Debug the issue
    analysis = optimizer.analyze_development_environment(project_root)
    
    # Generate optimization report
    optimization = {
        'success': True,
        'analysis': analysis,
        'recommendations': analysis.get('recommendations', []),
        'implementation_plan': [],
        'estimated_effort': {}
    }
    
    report = optimizer.generate_optimization_report(optimization)
    
    # Print to stdout
    print(report)
    
    # Save to file
    import time
    timestamp = int(time.time())
    report_file = f"dx_optimization_{timestamp}.md"
    
    with open(report_file, 'w') as f:
        f.write(report)
        
    print(f"\nOptimization report saved to: {report_file}")

if __name__ == "__main__":
    main()